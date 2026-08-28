<?php

declare(strict_types=1);

namespace Webware\Acl;

use Laminas\Permissions\Acl\Acl as LaminasAcl;
use Laminas\Permissions\Acl\Exception\ExceptionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\Registry;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Mezzio\Router\RouteCollectorInterface;
use Override;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use ValueError;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Assertion\AssertionAggregateFactory;
use Webware\Acl\Exception\RuntimeException;
use Webware\Acl\Query\FetchAclRoleRegistry;
use Webware\Acl\Query\FetchAllRules;
use Webware\Acl\Query\FetchDistinctResourceIds;
use Webware\Acl\Role\UserRoleIterator;
use Webware\Core\AclInterface;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;

use function is_array;
use function sort;
use function strrpos;
use function substr;

final class Acl extends LaminasAcl implements AclInterface
{
    private bool $loaded = false;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly AssertionAggregateFactory $factory,
        private readonly RouteCollectorInterface $routeCollector,
    ) {}

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function addResource($resource, $parent = null, bool $persist = false)
    {
        throw RuntimeException::forAclAddResource();
    }

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function addRole($role, $parents = null, bool $persist = false)
    {
        parent::addRole($role, $parents);

        if ($persist) {
            $roleId = $role instanceof RoleInterface ? $role->getRoleId() : $role;

            if (null === $parents) {
                $this->messageBus->handle(new SaveRoleCommand(null, $roleId, null));
            } else {
                $parentIds    = [];
                $parentsArray = is_array($parents) ? $parents : [$parents];
                foreach ($parentsArray as $parent) {
                    /** @var string|RoleInterface $parent */
                    $parentIds[] = $parent instanceof RoleInterface ? $parent->getRoleId() : $parent;
                }
                $this->messageBus->handle(new SaveRoleCommand(null, $roleId, $parentIds));
            }
        }

        return $this;
    }

    #[Override]
    public function getResourceParentId(string $resourceId): ?string
    {
        if (! $this->hasResource($resourceId)) {
            return null;
        }

        return $this->resources[$resourceId]['parent']?->getResourceId();
    }

    /**
     * @return array<string, array<string>>
     * @throws ExceptionInterface
     */
    #[Override]
    public function getRoles(): array
    {
        $registry = $this->getRoleRegistry();
        $result   = [];
        foreach (array_keys($registry->getRoles()) as $roleId) {
            $parents = [];
            foreach ($registry->getParents($roleId) as $parentId => $parent) {
                $parents[] = $parentId;
            }
            $result[$roleId] = $parents;
        }

        return $result;
    }

    /**
     * @throws ExceptionInterface
     * @throws SqlException
     * @throws ValueError
     */
    #[Override]
    public function isAllowed(
        $role = null,
        $resource = null,
        $privilege = null,
    ): bool {
        if (null === $role) {
            return false;
        }

        $this->load();

        // FAIL CLOSED — intentional, do not change to true.
        // Routes must be explicitly registered as ACL resources to be accessible.
        // This is a hard requirement; unregistered routes are always denied.
        if (! $this->hasResource($resource)) {
            return false;
        }

        foreach (new UserRoleIterator($role) as $roleProxy) {
            if (parent::isAllowed($roleProxy, $resource, $privilege)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ExceptionInterface
     * @throws SqlException
     * @throws ValueError
     */
    #[Override]
    public function isAllowedRoute(
        ?UserInterface $user,
        ResourceInterface $resource,
    ): bool {
        return $this->isAllowed($user, $resource);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    protected function getRoleRegistry()
    {
        if (null === $this->roleRegistry) {
            /** @var Registry $registry */
            $registry           = $this->messageBus->handle(new FetchAclRoleRegistry())->getResult();
            $this->roleRegistry = $registry;
        }

        return $this->roleRegistry;
    }

    /**
     * @throws ExceptionInterface
     * @throws SqlException
     * @throws ValueError
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        /** @var array<int, array{type: string, roleId: string, resourceId: string, assertions: string[], parentResourceId: string|null}> $allRules */
        $allRules = $this->messageBus->handle(new FetchAllRules())->getResult();

        // Build explicit parent map from DB data
        $explicitParents = [];
        foreach ($allRules as $rule) {
            if (null === $rule['parentResourceId']) {
                continue;
            }

            $explicitParents[$rule['resourceId']] = $rule['parentResourceId'];
        }

        /** @var string[] $resourceIds */
        $resourceIds = $this->messageBus->handle(new FetchDistinctResourceIds())->getResult();
        sort($resourceIds);

        foreach ($resourceIds as $resourceId) {
            if ($this->hasResource($resourceId)) {
                continue;
            }
            // Use explicit parentResourceId from DB — no fallback by design.
            // A missing parent here indicates inconsistent DB state and should surface, not be masked.
            $parent = $explicitParents[$resourceId] ?? null;
            parent::addResource($resourceId, $parent);
        }

        foreach ($allRules as $rule) {
            $type = RuleType::from($rule['type'])->toAclConstant();
            $this->setRule(
                self::OP_ADD,
                $type,
                $rule['roleId'],
                $rule['resourceId'],
                null,
                ($this->factory)($rule['assertions']),
            );
        }

        if ($this->hasRole(self::DEVELOPER_ROLE_ID)) {
            $this->setRule(self::OP_ADD, self::TYPE_ALLOW, self::DEVELOPER_ROLE_ID);
        }

        // Register all known routes as resources so the full hierarchy is available.
        // Routes with no rules are registered here under their nearest ancestor.
        foreach ($this->routeCollector->getRoutes() as $route) {
            $name = $route->getName();
            if (null === $name || '' === $name || $this->hasResource($name)) {
                continue;
            }
            $candidate = $name;
            $previous  = '';
            $parent    = null;
            while (false !== ($pos = strrpos($candidate, '.')) && $candidate !== $previous) {
                $previous  = $candidate;
                $candidate = substr($candidate, 0, $pos);

                if ($this->hasResource($candidate)) {
                    $parent = $candidate;

                    break;
                }
            }
            if (null === $parent) {
                parent::addResource($name);

                continue;
            }
            parent::addResource($name, $parent);
        }

        $this->loaded = true;
    }
}

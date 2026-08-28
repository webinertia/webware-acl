<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Acl;
use Webware\Acl\Assertion\AssertionAggregateFactory;
use Webware\Acl\AssertionManager;
use Webware\Acl\Entity\Role;
use Webware\Acl\Exception\RuntimeException;
use Webware\Core\UserInterface;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

use function array_map;
use function json_encode;

#[CoversClass(Acl::class)]
final class AclTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function addResourceAlwaysThrowsRuntimeException(): void
    {
        $acl = $this->createAcl([]);
        $this->expectException(RuntimeException::class);
        $acl->addResource('dashboard');
    }

    #[Test]
    public function addRolePersistsNullParentsWhenNoParentsGiven(): void
    {
        $acl = $this->createAcl([
            [$this->roleRow(1, 'Admin', null)],
            [false],
            [],
        ]);
        $acl->addRole('Editor', null, true);
        self::assertSame(
            [
                'Admin'  => [],
                'Editor' => [],
            ],
            $acl->getRoles(),
        );
    }

    #[Test]
    public function addRolePersistsRoleWithRoleInstanceParents(): void
    {
        $acl = $this->createAcl([
            [$this->roleRow(1, 'Admin', null)],
            [false],
            [],
        ]);
        $acl->addRole('Editor', new Role(roleId: 'Admin'), true);
        self::assertSame(
            [
                'Admin'  => [],
                'Editor' => ['Admin'],
            ],
            $acl->getRoles(),
        );
    }

    #[Test]
    public function addRolePersistsRoleWithStringParents(): void
    {
        $acl = $this->createAcl([
            [$this->roleRow(1, 'Admin', null)],
            [false],
            [],
        ]);
        $acl->addRole('Editor', ['Admin'], true);
        self::assertSame(
            [
                'Admin'  => [],
                'Editor' => ['Admin'],
            ],
            $acl->getRoles(),
        );
    }

    #[Test]
    public function addRoleWithoutPersistRegistersWithoutSaving(): void
    {
        $acl = $this->createAcl([[]]);
        $acl->addRole('Guest');
        self::assertSame(['Guest' => []], $acl->getRoles());
    }

    #[Test]
    public function allowRuleGrantsAccessToRegisteredResource(): void
    {
        $acl = $this->createAcl([
            [$this->ruleRow('Allow', 'Admin', 'dashboard')],
            [$this->resourceIdRow('dashboard')],
            [$this->roleRow(1, 'Admin', null)],
        ]);
        self::assertTrue($acl->isAllowed($this->createUser(['Admin']), 'dashboard'));
    }

    #[Test]
    public function assertionAggregateBuiltByFactoryIsEvaluated(): void
    {
        $assertion = $this->createStub(AssertionInterface::class);
        $assertion->method('assert')->willReturn(true);
        $manager = $this->createAssertionManager(['Ownership' => $assertion]);
        $acl     = $this->createAcl(
            [
                [$this->ruleRow('Allow', 'Admin', 'dashboard', ['Ownership'])],
                [$this->resourceIdRow('dashboard')],
                [$this->roleRow(1, 'Admin', null)],
            ],
            [],
            $manager,
        );
        self::assertTrue($acl->isAllowed($this->createUser(['Admin']), 'dashboard'));
    }

    #[Test]
    public function denyRuleOverridesAllowRule(): void
    {
        $acl = $this->createAcl([
            [
                $this->ruleRow('Allow', 'Admin', 'dashboard'),
                $this->ruleRow('Deny', 'Admin', 'dashboard'),
            ],
            [$this->resourceIdRow('dashboard')],
            [$this->roleRow(1, 'Admin', null)],
        ]);
        self::assertFalse($acl->isAllowed($this->createUser(['Admin']), 'dashboard'));
    }

    #[Test]
    public function developerRoleReceivesBlanketAllowance(): void
    {
        $acl = $this->createAcl([
            [],
            [$this->resourceIdRow('anything')],
            [$this->roleRow(1, 'Developer', null)],
        ]);
        self::assertTrue($acl->isAllowed($this->createUser(['Developer']), 'anything'));
    }

    #[Test]
    public function duplicateResourceIdIsSkippedAfterFirstRegistration(): void
    {
        $acl = $this->createAcl([
            [],
            [
                $this->resourceIdRow('dashboard'),
                $this->resourceIdRow('dashboard'),
            ],
            [$this->roleRow(1, 'Admin', null)],
        ]);

        $acl->isAllowed($this->createUser(['Admin']), 'dashboard');

        self::assertTrue($acl->hasResource('dashboard'));
    }

    #[Test]
    public function explicitParentResourceIdFromDbIsUsedAsResourceParent(): void
    {
        $acl = $this->createAcl([
            [$this->ruleRow('Allow', 'Admin', 'admin.users', null, 'admin')],
            [
                $this->resourceIdRow('admin'),
                $this->resourceIdRow('admin.users'),
            ],
            [$this->roleRow(1, 'Admin', null)],
        ]);
        self::assertTrue($acl->isAllowed($this->createUser(['Admin']), 'admin.users'));
        self::assertSame('admin', $acl->getResourceParentId('admin.users'));
    }

    #[Test]
    public function getResourceParentIdReturnsNullForUnknownResource(): void
    {
        $acl = $this->createAcl([]);
        self::assertNull($acl->getResourceParentId('not-a-resource'));
    }

    #[Test]
    public function getRolesReturnsRoleToParentsMappingFromRegistry(): void
    {
        $acl = $this->createAcl([
            [
                $this->roleRow(1, 'Admin', null),
                $this->roleRow(2, 'Manager', '["Admin"]'),
                $this->roleRow(3, 'Developer', null),
            ],
        ]);
        self::assertSame(
            [
                'Admin'     => [],
                'Developer' => [],
                'Manager'   => ['Admin'],
            ],
            $acl->getRoles(),
        );
    }

    #[Test]
    public function isAllowedReturnsFalseForUnregisteredResourceAfterLoad(): void
    {
        $acl = $this->createAcl([
            [],
            [],
            [$this->roleRow(1, 'Admin', null)],
        ]);
        self::assertFalse($acl->isAllowed($this->createUser(['Admin']), 'dashboard'));
    }

    #[Test]
    public function isAllowedReturnsFalseWhenRoleIsNullWithoutLoading(): void
    {
        $acl = $this->createAcl([]);
        self::assertFalse($acl->isAllowed(null, 'dashboard'));
    }

    #[Test]
    public function isAllowedRouteDelegatesToIsAllowedForAuthenticatedUser(): void
    {
        $acl = $this->createAcl([
            [$this->ruleRow('Allow', 'Admin', 'dashboard')],
            [$this->resourceIdRow('dashboard')],
            [$this->roleRow(1, 'Admin', null)],
        ]);
        $resource = $this->createStub(ResourceInterface::class);
        $resource->method('getResourceId')->willReturn('dashboard');
        self::assertTrue($acl->isAllowedRoute($this->createUser(['Admin']), $resource));
    }

    #[Test]
    public function isAllowedRouteWithNullUserReturnsFalse(): void
    {
        $acl      = $this->createAcl([]);
        $resource = $this->createStub(ResourceInterface::class);
        self::assertFalse($acl->isAllowedRoute(null, $resource));
    }

    #[Test]
    public function loadIsIdempotentAcrossIsAllowedCalls(): void
    {
        $acl = $this->createAcl([
            [$this->ruleRow('Allow', 'Admin', 'dashboard')],
            [$this->resourceIdRow('dashboard')],
            [$this->roleRow(1, 'Admin', null)],
        ]);
        $user = $this->createUser(['Admin']);
        self::assertTrue($acl->isAllowed($user, 'dashboard'));
        self::assertTrue($acl->isAllowed($user, 'dashboard'));
    }

    #[Test]
    public function routeNameMatchingExistingResourceIsSkippedDuringLoad(): void
    {
        $acl = $this->createAcl([
            [$this->ruleRow('Allow', 'Admin', 'dashboard')],
            [$this->resourceIdRow('dashboard')],
            [$this->roleRow(1, 'Admin', null)],
        ], ['dashboard']);

        self::assertTrue($acl->isAllowed($this->createUser(['Admin']), 'dashboard'));
    }

    #[Test]
    public function routeNamesAreRegisteredUnderNearestExistingAncestor(): void
    {
        $acl = $this->createAcl([
            [],
            [$this->resourceIdRow('admin')],
            [],
        ], ['admin.users', 'admin.users.edit']);
        $acl->isAllowed($this->createUser([]), 'admin.users');
        self::assertSame('admin', $acl->getResourceParentId('admin.users'));
        self::assertSame('admin.users', $acl->getResourceParentId('admin.users.edit'));
    }

    #[Test]
    public function routeWithoutAncestorIsRegisteredAsRoot(): void
    {
        $acl = $this->createAcl([[], [], []], ['dashboard.index']);
        $acl->isAllowed($this->createUser([]), 'dashboard.index');
        self::assertNull($acl->getResourceParentId('dashboard.index'));
    }

    #[Test]
    public function userWithMultipleRolesIsAllowedWhenAnyRolePasses(): void
    {
        $acl = $this->createAcl([
            [$this->ruleRow('Allow', 'Admin', 'dashboard')],
            [$this->resourceIdRow('dashboard')],
            [
                $this->roleRow(1, 'Guest', null),
                $this->roleRow(2, 'Admin', null),
            ],
        ]);
        self::assertTrue($acl->isAllowed($this->createUser(['Guest', 'Admin']), 'dashboard'));
    }

    #[Test]
    public function userWithNoRolesIsDenied(): void
    {
        $acl = $this->createAcl([
            [$this->ruleRow('Allow', 'Admin', 'dashboard')],
            [$this->resourceIdRow('dashboard')],
            [$this->roleRow(1, 'Admin', null)],
        ]);
        self::assertFalse($acl->isAllowed($this->createUser([]), 'dashboard'));
    }

    /**
     * @param list<list<mixed>> $statementResults Result row queues, one inner list per statement issued.
     * @param list<string> $routeNames
     */
    private function createAcl(
        array $statementResults,
        array $routeNames = [],
        ?AssertionManager $assertionManager = null,
    ): Acl {
        $adapter = $this->createAdapter($statementResults);
        return new Acl(
            $this->createQueryBus($adapter),
            new AssertionAggregateFactory($assertionManager ?? $this->createAssertionManager()),
            $this->createRouteCollector($routeNames),
        );
    }

    /**
     * @param array<string, AssertionInterface>|null $services
     */
    private function createAssertionManager(?array $services = null): AssertionManager
    {
        $manager = new AssertionManager(new ServiceManager());
        if (null !== $services) {
            $manager->configure(['services' => $services]);
        }
        return $manager;
    }

    /**
     * @param list<string> $routeNames
     */
    private function createRouteCollector(array $routeNames): RouteCollectorInterface
    {
        $collector = $this->createStub(RouteCollectorInterface::class);
        $routes    = array_map(
            fn(string $name): Route => new Route('/', $this->createStub(MiddlewareInterface::class), null, $name),
            $routeNames,
        );
        $collector->method('getRoutes')->willReturn($routes);
        return $collector;
    }

    /**
     * @param list<mixed> $rows Each row is an array (result row) or false (no row).
     */
    /**
     * @param list<string> $roles
     */
    private function createUser(array $roles): UserInterface
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn($roles);
        return $user;
    }

    /**
     * @return array{resourceId: string}
     */
    private function resourceIdRow(string $resourceId): array
    {
        return ['resourceId' => $resourceId];
    }

    /**
     * @return array{id: int, roleId: string, parentId: string|null}
     */
    private function roleRow(int $id, string $roleId, ?string $parentJson): array
    {
        return ['id' => $id, 'roleId' => $roleId, 'parentId' => $parentJson];
    }

    /**
     * @return array{type: string, roleId: string, resourceId: string, assertions: list<string>|null, parentResourceId: string|null}
     */
    private function ruleRow(
        string $type,
        string $roleId,
        string $resourceId,
        ?array $assertions = null,
        ?string $parentResourceId = null,
    ): array {
        return [
            'type'             => $type,
            'roleId'           => $roleId,
            'resourceId'       => $resourceId,
            'assertions'       => null === $assertions ? null : json_encode($assertions),
            'parentResourceId' => $parentResourceId,
        ];
    }
}

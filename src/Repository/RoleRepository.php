<?php

declare(strict_types=1);

namespace Webware\Acl\Repository;

use Laminas\Permissions\Acl\Exception\ExceptionInterface as AclException;
use Laminas\Permissions\Acl\Role\Registry;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\TableGateway\TableGateway;
use Webware\Acl\Entity\Role;

use function array_shift;
use function json_encode;

final class RoleRepository
{
    public function __construct(
        private readonly TableGateway $gateway,
    ) {}

    public function delete(string $roleId): void
    {
        $this->gateway->delete(['roleId' => $roleId]);
    }

    /**
     * @throws AclException
     */
    public function fetchAclRoleRegistry(): Registry
    {
        $roles = $this->fetchAll();

        // Index by roleId for O(1) lookup
        $map = [];
        foreach ($roles as $role) {
            $map[$role->getRoleId()] = $role;
        }

        // Kahn's topological sort — build in-degree and adjacency list
        $inDegree = [];
        $children = [];
        foreach ($map as $roleId => $role) {
            $inDegree[$roleId] ??= 0;
            foreach ($role->parentId ?? [] as $parent) {
                $parentId = $parent->getRoleId();
                if (isset($map[$parentId])) {
                    $inDegree[$roleId]++;
                    $children[$parentId][] = $roleId;
                }
            }
        }

        // Seed the queue with roots (roles that have no known parents)
        $queue = [];
        foreach ($inDegree as $roleId => $degree) {
            if (0 !== $degree) {
                continue;
            }

            $queue[] = $roleId;
        }

        $registry = new Registry();
        while ([] !== $queue) {
            $roleId = array_shift($queue);
            $role   = $map[$roleId];
            $registry->add($role, $role?->parentId ?: null);
            foreach ($children[$roleId] ?? [] as $childId) {
                if (--$inDegree[$childId] !== 0) {
                    continue;
                }

                $queue[] = $childId;
            }
        }

        return $registry;
    }

    /**
     * @return Role[]
     */
    public function fetchAll(): array
    {
        $roles = [];
        foreach ($this->gateway->select() as $role) {
            $roles[] = $role;
        }

        return $roles;
    }

    /**
     * Returns all role_ids whose parent_id JSON array contains the given roleId.
     *
     * @return string[]
     */
    public function fetchDirectChildren(string $roleId): array
    {
        $select = $this->gateway->getSql()->select()->columns(['roleId']);
        $select->where->expression('JSON_CONTAINS(parentId, JSON_QUOTE(?))', [$roleId]);

        $children = [];
        /** @var Role $role */
        foreach ($this->gateway->selectWith($select) as $role) {
            $children[] = $role->getRoleId();
        }

        return $children;
    }

    /**
     * Removes the given roleId from the parentId JSON array of any role that lists it as a parent.
     *
     * @throws SqlException
     */
    public function removeFromParents(string $roleId): void
    {
        $select = $this->gateway->getSql()->select()->columns(['id', 'parentId']);
        $select->where->expression('JSON_CONTAINS(parentId, JSON_QUOTE(?))', [$roleId]);

        foreach ($this->gateway->selectWith($select) as $role) {
            /** @var Role $role */
            $parents = [];
            foreach ($role->getParentId() ?? [] as $parent) {
                /** @var Role $parent */
                $parentId = $parent->getRoleId();
                if ($parentId !== $roleId) {
                    $parents[] = $parentId;
                }
            }

            $this->gateway->update(['parentId' => json_encode($parents)], ['id' => $role->id]);
        }
    }

    /**
     * Insert or update a role. parentId is JSON-encoded inside this method.
     *
     * @param string[]|null $parents
     * @throws SqlException
     */
    public function save(string $roleId, ?array $parents): int|string|false
    {
        $data = [
            'roleId'   => $roleId,
            'parentId' => json_encode($parents),
        ];

        $select = $this->gateway->getSql()->select();
        $select->columns(['id'])->where(['roleId' => $roleId])->limit(1);

        /** @var Role|null $existing */
        $existing = $this->gateway->selectWith($select)->current();

        if (null === $existing) {
            $this->gateway->insert($data);

            return $this->gateway->getLastInsertValue();
        }

        $result = $this->gateway->update(['parentId' => $data['parentId']], ['roleId' => $roleId]);
        $id     = $existing->id;

        return $result > 0 && null !== $id ? $id : false;
    }
}

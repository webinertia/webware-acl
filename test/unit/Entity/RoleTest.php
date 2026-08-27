<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Entity\Role;

#[CoversClass(Role::class)]
final class RoleTest extends TestCase
{
    #[Test]
    public function arrayParentIdOfRoleInstancesIsPreserved(): void
    {
        $admin = new Role(roleId: 'Admin');
        $role  = new Role(parentId: [$admin]);

        self::assertSame([$admin], $role->getParentId());
    }

    #[Test]
    public function arrayParentIdOfStringsIsMappedToRoleInstances(): void
    {
        $role    = new Role(parentId: ['Admin', 'Manager']);
        $parents = $role->getParentId();

        self::assertIsArray($parents);
        self::assertSame('Admin', $parents[0]->getRoleId());
        self::assertSame('Manager', $parents[1]->getRoleId());
    }

    #[Test]
    public function defaultsToNullIdentifiersAndNoParents(): void
    {
        $role = new Role();

        self::assertNull($role->getId());
        self::assertNull($role->getRoleId());
        self::assertNull($role->getParentId());
    }

    #[Test]
    public function exposesIdAndRoleId(): void
    {
        $role = new Role(7, 'Admin');

        self::assertSame(7, $role->getId());
        self::assertSame('Admin', $role->getRoleId());
    }

    #[Test]
    public function nullParentIdStaysNull(): void
    {
        self::assertNull(new Role(parentId: null)->getParentId());
    }

    #[Test]
    public function populateBuildsNewRoleFromRow(): void
    {
        /** @var Role $role */
        $role = new Role()->populate(['id' => 5, 'roleId' => 'Editor', 'parentId' => null]);

        self::assertSame(5, $role->getId());
        self::assertSame('Editor', $role->getRoleId());
        self::assertNull($role->getParentId());
    }

    #[Test]
    public function stringParentIdIsDecodedAndMappedToRoleInstances(): void
    {
        $role    = new Role(parentId: '["Admin","Manager"]');
        $parents = $role->getParentId();

        self::assertIsArray($parents);
        self::assertCount(2, $parents);
        self::assertSame('Admin', $parents[0]->getRoleId());
        self::assertSame('Manager', $parents[1]->getRoleId());
    }

    #[Test]
    public function toArrayCastsPublicProperties(): void
    {
        self::assertSame(
            ['id' => 1, 'roleId' => 'Admin', 'parentId' => null],
            new Role(1, 'Admin', null)->toArray(),
        );
    }

    #[Test]
    public function withRowDataDelegatesToPopulate(): void
    {
        /** @var Role $role */
        $role = new Role()->withRowData(['id' => 2, 'roleId' => 'Admin', 'parentId' => null]);

        self::assertSame(2, $role->getId());
        self::assertSame('Admin', $role->getRoleId());
    }
}

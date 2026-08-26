<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Repository;

use PhpDb\Sql\Delete;
use PhpDb\Sql\Insert;
use PhpDb\Sql\Select;
use PhpDb\Sql\Update;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Entity\Role;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(RoleRepository::class)]
final class RoleRepositoryTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function deleteExecutesDeleteStatement(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [],
        ]));

        $repo->delete('Admin');

        self::assertInstanceOf(Delete::class, $this->preparedSqlObjects[0]);
    }

    #[Test]
    public function fetchAclRoleRegistryBuildsParentHierarchy(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [
                ['id' => 1, 'roleId' => 'Admin', 'parentId' => null],
                ['id' => 2, 'roleId' => 'Manager', 'parentId' => '["Admin"]'],
            ],
        ]));

        $registry = $repo->fetchAclRoleRegistry();

        self::assertArrayHasKey('Admin', $registry->getRoles());
        self::assertArrayHasKey('Manager', $registry->getRoles());
        self::assertArrayHasKey('Admin', $registry->getParents('Manager'));
    }

    #[Test]
    public function fetchAclRoleRegistryHandlesMultipleParents(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [
                ['id' => 1, 'roleId' => 'Admin', 'parentId' => null],
                ['id' => 2, 'roleId' => 'Editor', 'parentId' => null],
                ['id' => 3, 'roleId' => 'Manager', 'parentId' => '["Admin","Editor"]'],
            ],
        ]));

        $registry = $repo->fetchAclRoleRegistry();

        self::assertArrayHasKey('Admin', $registry->getRoles());
        self::assertArrayHasKey('Editor', $registry->getRoles());
        self::assertArrayHasKey('Manager', $registry->getRoles());
        self::assertArrayHasKey('Admin', $registry->getParents('Manager'));
        self::assertArrayHasKey('Editor', $registry->getParents('Manager'));
    }

    #[Test]
    public function fetchAllHydratesRoleEntities(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [
                ['id' => 1, 'roleId' => 'Admin', 'parentId' => null],
                ['id' => 2, 'roleId' => 'Manager', 'parentId' => '["Admin"]'],
            ],
        ]));

        $roles = $repo->fetchAll();

        self::assertCount(2, $roles);
        self::assertInstanceOf(Role::class, $roles[0]);
        self::assertSame('Admin', $roles[0]->getRoleId());
        self::assertSame('Manager', $roles[1]->getRoleId());

        $parents = $roles[1]->getParentId();
        self::assertIsArray($parents);
        self::assertSame('Admin', $parents[0]->getRoleId());
    }

    #[Test]
    public function fetchDirectChildrenReturnsRoleIds(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [
                ['roleId' => 'Editor'],
                ['roleId' => 'Manager'],
            ],
        ]));

        self::assertSame(['Editor', 'Manager'], $repo->fetchDirectChildren('Admin'));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
    }

    #[Test]
    public function removeFromParentsUpdatesEveryChild(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [
                ['id' => 1, 'parentId' => '["Admin","Editor"]'],
                ['id' => 2, 'parentId' => '["Editor"]'],
            ],
            [],
            [],
        ]));

        $repo->removeFromParents('Editor');

        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
        self::assertInstanceOf(Update::class, $this->preparedSqlObjects[1]);
        self::assertInstanceOf(Update::class, $this->preparedSqlObjects[2]);
    }

    #[Test]
    public function saveInsertsNewRoleAndReturnsGeneratedId(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [],
            [],
        ]));

        self::assertSame(7, $repo->save('Editor', ['Admin']));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
        self::assertInstanceOf(Insert::class, $this->preparedSqlObjects[1]);
    }

    #[Test]
    public function saveUpdatesExistingRoleAndReturnsRowId(): void
    {
        $repo = new RoleRepository($this->createAdapter([
            [['id' => 42]],
            [],
        ]));

        self::assertSame(42, $repo->save('Admin', ['Editor']));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
        self::assertInstanceOf(Update::class, $this->preparedSqlObjects[1]);
    }
}

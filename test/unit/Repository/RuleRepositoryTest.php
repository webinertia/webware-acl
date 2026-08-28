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
use Webware\Acl\Repository\RuleRepository;
use Webware\Acl\RuleType;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(RuleRepository::class)]
final class RuleRepositoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function deleteReturnsFalseWhenNoRowsAffected(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [],
        ], [0]));

        self::assertFalse($repo->delete('Admin', 'dashboard'));
    }

    #[Test]
    public function deleteReturnsTrueWhenRowsAffected(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [],
        ]));

        self::assertTrue($repo->delete('Admin', 'dashboard'));
        self::assertInstanceOf(Delete::class, $this->preparedSqlObjects[0]);
    }

    #[Test]
    public function fetchAllDecodesAssertions(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'dashboard',
                    'assertions'       => '["Ownership"]',
                    'parentResourceId' => null,
                ],
                [
                    'type'       => 'Deny',
                    'roleId'     => 'Guest',
                    'resourceId' => 'dashboard',
                    'assertions' => null,
                ],
            ],
        ]));

        self::assertSame(
            [
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'dashboard',
                    'assertions'       => ['Ownership'],
                    'parentResourceId' => null,
                ],
                [
                    'type'             => 'Deny',
                    'roleId'           => 'Guest',
                    'resourceId'       => 'dashboard',
                    'assertions'       => null,
                    'parentResourceId' => null,
                ],
            ],
            $repo->fetchAll(),
        );
    }

    #[Test]
    public function fetchDistinctResourceIdsReturnsIds(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [
                ['resourceId' => 'dashboard'],
                ['resourceId' => 'admin'],
            ],
        ]));

        self::assertSame(['dashboard', 'admin'], $repo->fetchDistinctResourceIds());
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
    }

    #[Test]
    public function findByRoleAndResourceReturnsNullAssertionsWhenNull(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [
                [
                    'type'       => 'Allow',
                    'roleId'     => 'Admin',
                    'resourceId' => 'dashboard',
                    'assertions' => null,
                ],
            ],
        ]));

        self::assertSame(
            [
                'type'       => 'Allow',
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'assertions' => null,
            ],
            $repo->findByRoleAndResource('Admin', 'dashboard'),
        );
    }

    #[Test]
    public function findByRoleAndResourceReturnsNullWhenMissing(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [],
        ]));

        self::assertNull($repo->findByRoleAndResource('Admin', 'dashboard'));
    }

    #[Test]
    public function findByRoleAndResourceReturnsRow(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [
                [
                    'type'       => 'Allow',
                    'roleId'     => 'Admin',
                    'resourceId' => 'dashboard',
                    'assertions' => '["Ownership"]',
                ],
            ],
        ]));

        self::assertSame(
            [
                'type'       => 'Allow',
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'assertions' => ['Ownership'],
            ],
            $repo->findByRoleAndResource('Admin', 'dashboard'),
        );
    }

    #[Test]
    public function saveInsertsNewRuleAndReturnsGeneratedId(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [],
            [],
        ]));

        self::assertSame(7, $repo->save(RuleType::Allow, 'Admin', 'dashboard', ['Ownership'], 'admin'));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
        self::assertInstanceOf(Insert::class, $this->preparedSqlObjects[1]);
    }

    #[Test]
    public function saveTreatsEmptyAssertionsAsNull(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [],
            [],
        ]));

        self::assertSame(7, $repo->save(RuleType::Allow, 'Admin', 'dashboard', [], null));
        self::assertInstanceOf(Insert::class, $this->preparedSqlObjects[1]);
    }

    #[Test]
    public function saveUpdatesExistingRuleAndReturnsRowId(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [['id' => 42]],
            [],
        ]));

        self::assertSame(42, $repo->save(RuleType::Deny, 'Admin', 'dashboard', null, null));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
        self::assertInstanceOf(Update::class, $this->preparedSqlObjects[1]);
    }

    #[Test]
    public function saveUpdatesExistingRuleIncludingParentAndAssertions(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [['id' => 42]],
            [],
        ]));

        self::assertSame(42, $repo->save(RuleType::Deny, 'Admin', 'dashboard', ['Ownership'], 'admin'));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);
        self::assertInstanceOf(Update::class, $this->preparedSqlObjects[1]);
    }

    #[Test]
    public function updateTypeReturnsTrueWhenRowsAffected(): void
    {
        $repo = new RuleRepository($this->createAdapter([
            [],
        ]));

        self::assertTrue($repo->updateType('Admin', 'dashboard', RuleType::Deny));
        self::assertInstanceOf(Update::class, $this->preparedSqlObjects[0]);
    }
}

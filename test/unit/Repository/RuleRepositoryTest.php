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
        $repo = $this->createRuleRepository($this->createAdapter([
            [],
        ], [0]));

        self::assertFalse($repo->delete('Admin', 'dashboard'));
    }

    #[Test]
    public function deleteReturnsTrueWhenRowsAffected(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [],
        ]));

        self::assertTrue($repo->delete('Admin', 'dashboard'));
        self::assertInstanceOf(Delete::class, $this->preparedSqlObjects[0]);
        self::assertContains('roleId', $this->whereValues(0));
        self::assertContains('resourceId', $this->whereValues(0));
    }

    #[Test]
    public function hasRuleReturnsFalseWhenMissing(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [],
        ]));

        self::assertFalse($repo->hasRule('Admin', 'dashboard'));
    }

    #[Test]
    public function hasRuleReturnsTrueWhenRuleExists(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [['id' => 42]],
        ]));

        self::assertTrue($repo->hasRule('Admin', 'dashboard'));

        $select = $this->preparedSqlObjects[0];
        self::assertInstanceOf(Select::class, $select);
        self::assertSame(['id'], $select->getRawState('columns'));
        self::assertSame(1, $select->getRawState('limit'));
        self::assertContains('roleId', $this->whereValues(0));
        self::assertContains('resourceId', $this->whereValues(0));
    }

    #[Test]
    public function saveInsertsNewRuleAndReturnsGeneratedId(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [],
            [],
        ]));

        self::assertSame(7, $repo->save(RuleType::Allow, 'Admin', 'dashboard', ['Ownership'], 'admin'));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);

        $insert = $this->preparedSqlObjects[1];
        self::assertInstanceOf(Insert::class, $insert);
        self::assertSame(
            ['type', 'roleId', 'resourceId', 'parentResourceId', 'assertions'],
            $insert->getRawState('columns'),
        );
    }

    #[Test]
    public function saveReturnsIdWhenUpdateAffectsNoRows(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [['id' => 42]],
            [],
        ], [1, 0]));

        self::assertSame(42, $repo->save(RuleType::Deny, 'Admin', 'dashboard', null, null));
    }

    #[Test]
    public function saveTreatsEmptyAssertionsAsNull(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [],
            [],
        ]));

        self::assertSame(7, $repo->save(RuleType::Allow, 'Admin', 'dashboard', [], null));

        $insert = $this->preparedSqlObjects[1];
        self::assertInstanceOf(Insert::class, $insert);
        self::assertSame(['type', 'roleId', 'resourceId'], $insert->getRawState('columns'));
    }

    #[Test]
    public function saveTreatsSingleEmptyStringAssertionsAsNull(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [],
            [],
        ]));

        self::assertSame(7, $repo->save(RuleType::Allow, 'Admin', 'dashboard', [''], null));

        $insert = $this->preparedSqlObjects[1];
        self::assertInstanceOf(Insert::class, $insert);
        self::assertSame(['type', 'roleId', 'resourceId'], $insert->getRawState('columns'));
    }

    #[Test]
    public function saveUpdatesExistingRuleAndReturnsRowId(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [['id' => 42]],
            [],
        ]));

        self::assertSame(42, $repo->save(RuleType::Deny, 'Admin', 'dashboard', null, null));

        $select = $this->preparedSqlObjects[0];
        self::assertInstanceOf(Select::class, $select);
        self::assertSame(['id'], $select->getRawState('columns'));
        self::assertSame(1, $select->getRawState('limit'));
        self::assertContains('roleId', $this->whereValues(0));
        self::assertContains('resourceId', $this->whereValues(0));

        $update = $this->preparedSqlObjects[1];
        self::assertInstanceOf(Update::class, $update);
        self::assertSame(['type' => 'Deny'], $update->getRawState('set'));
        self::assertContains('roleId', $this->whereValues(1));
        self::assertContains('resourceId', $this->whereValues(1));
    }

    #[Test]
    public function saveUpdatesExistingRuleIncludingParentAndAssertions(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [['id' => 42]],
            [],
        ]));

        self::assertSame(42, $repo->save(RuleType::Deny, 'Admin', 'dashboard', ['Ownership'], 'admin'));
        self::assertInstanceOf(Select::class, $this->preparedSqlObjects[0]);

        $update = $this->preparedSqlObjects[1];
        self::assertInstanceOf(Update::class, $update);
        self::assertSame(
            ['type' => 'Deny', 'parentResourceId' => 'admin', 'assertions' => '["Ownership"]'],
            $update->getRawState('set'),
        );
    }

    #[Test]
    public function updateTypeReturnsFalseWhenNoRowsAffected(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([[]], [0]));

        self::assertFalse($repo->updateType('Admin', 'dashboard', RuleType::Deny));
    }

    #[Test]
    public function updateTypeReturnsTrueWhenRowsAffected(): void
    {
        $repo = $this->createRuleRepository($this->createAdapter([
            [],
        ]));

        self::assertTrue($repo->updateType('Admin', 'dashboard', RuleType::Deny));

        $update = $this->preparedSqlObjects[0];
        self::assertInstanceOf(Update::class, $update);
        self::assertSame(['type' => 'Deny'], $update->getRawState('set'));
        self::assertContains('roleId', $this->whereValues(0));
        self::assertContains('resourceId', $this->whereValues(0));
    }

    private function whereValues(int $index): array
    {
        $values = [];
        foreach ($this->preparedSqlObjects[$index]->getRawState('where')->getExpressionData()['values'] as $argument) {
            $values[] = $argument->getValue();
        }

        return $values;
    }
}

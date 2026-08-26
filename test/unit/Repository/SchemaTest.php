<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Repository;

use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Repository\Schema;

#[CoversClass(Schema::class)]
final class SchemaTest extends TestCase
{
    #[Test]
    public function fromAcceptsTheStoredValues(): void
    {
        self::assertSame(Schema::Roles, Schema::from('acl_role'));
        self::assertSame(Schema::Rules, Schema::from('acl_rule'));
    }

    #[Test]
    public function rolesCaseHasAclRoleValue(): void
    {
        self::assertSame('acl_role', Schema::Roles->value);
        self::assertSame('acl_role', Schema::Roles->table()->getTable());
    }

    #[Test]
    public function rulesCaseHasAclRuleValue(): void
    {
        self::assertSame('acl_rule', Schema::Rules->value);
        self::assertSame('acl_rule', Schema::Rules->table()->getTable());
    }

    #[Test]
    public function tableReturnsTableIdentifier(): void
    {
        self::assertInstanceOf(TableIdentifier::class, Schema::Roles->table());
        self::assertInstanceOf(TableIdentifier::class, Schema::Rules->table());
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Entity\Rule;
use Webware\Acl\RuleType;

#[CoversClass(Rule::class)]
final class RuleTest extends TestCase
{
    #[Test]
    public function assertionsAreDeepCopiedWhenProvided(): void
    {
        self::assertSame(['Ownership'], new Rule(assertions: ['Ownership'])->assertions);
    }

    #[Test]
    public function defaultsToAllowTypeAndNullIdentifiers(): void
    {
        $rule = new Rule();

        self::assertSame(RuleType::Allow, $rule->type);
        self::assertNull($rule->getRoleId());
        self::assertNull($rule->getResourceId());
        self::assertNull($rule->assertions);
        self::assertNull($rule->parentResourceId);
    }

    #[Test]
    public function emptyAssertionsStayEmpty(): void
    {
        self::assertSame([], new Rule(assertions: [])->assertions);
    }

    #[Test]
    public function exposesRoleIdAndResourceId(): void
    {
        $rule = new Rule(
            roleId    : 'Admin',
            resourceId: 'dashboard',
        );

        self::assertSame('Admin', $rule->getRoleId());
        self::assertSame('dashboard', $rule->getResourceId());
    }

    #[Test]
    public function populateBuildsNewRuleFromRow(): void
    {
        /** @var Rule $rule */
        $rule = new Rule()->populate([
            'id'               => 1,
            'type'             => RuleType::Deny,
            'roleId'           => 'Admin',
            'resourceId'       => 'dashboard',
            'assertions'       => null,
            'parentResourceId' => null,
        ]);

        self::assertSame(1, $rule->id);
        self::assertSame(RuleType::Deny, $rule->type);
        self::assertSame('Admin', $rule->getRoleId());
        self::assertSame('dashboard', $rule->getResourceId());
    }

    #[Test]
    public function ruleTypeInstanceIsPreserved(): void
    {
        self::assertSame(RuleType::Deny, new Rule(type: RuleType::Deny)->type);
    }

    #[Test]
    public function toArrayCastsPublicProperties(): void
    {
        self::assertSame(
            [
                'id'               => 1,
                'type'             => RuleType::Deny,
                'roleId'           => 'Admin',
                'resourceId'       => 'dashboard',
                'assertions'       => ['Ownership'],
                'parentResourceId' => 'admin',
            ],
            new Rule(1, RuleType::Deny, 'Admin', 'dashboard', ['Ownership'], 'admin')->toArray(),
        );
    }

    #[Test]
    public function withRowDataDelegatesToPopulate(): void
    {
        $rule = new Rule()->withRowData([
            'id'               => 2,
            'type'             => RuleType::Deny,
            'roleId'           => 'Admin',
            'resourceId'       => 'dashboard',
            'assertions'       => ['Ownership'],
            'parentResourceId' => 'admin',
        ]);

        self::assertSame(2, $rule->id);
        self::assertSame(RuleType::Deny, $rule->type);
        self::assertSame(['Ownership'], $rule->assertions);
        self::assertSame('admin', $rule->parentResourceId);
    }
}

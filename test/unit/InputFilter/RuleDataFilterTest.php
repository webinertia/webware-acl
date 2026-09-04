<?php

declare(strict_types=1);

namespace WebwareTest\Acl\InputFilter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\InputFilter\RuleDataFilter;
use Webware\Acl\RuleType;
use WebwareTest\Acl\Support\InputFilterHelper;

#[CoversClass(RuleDataFilter::class)]
final class RuleDataFilterTest extends TestCase
{
    #[Test]
    public function absentAssertionsFallbackToNull(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'Allow']);

        self::assertTrue($result->valid());
        self::assertSame(
            [
                'resourceId' => 'dashboard',
                'type'       => RuleType::Allow,
                'roleId'     => 'Admin',
                'assertions' => null,
            ],
            $result->value(),
        );
    }

    #[Test]
    public function denyTypeIsConvertedToEnum(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'Deny']);

        self::assertTrue($result->valid());
        self::assertSame(RuleType::Deny, $result->value()['type']);
    }

    #[Test]
    public function emptyStringAssertionsBecomeNull(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate([
            'roleId'     => 'Admin',
            'resourceId' => 'dashboard',
            'type'       => 'Allow',
            'assertions' => '',
        ]);

        self::assertTrue($result->valid());
        self::assertNull($result->value()['assertions']);
    }

    #[Test]
    public function filtersAndNormalizesRuleData(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate([
            'roleId'     => ' Admin ',
            'resourceId' => 'dashboard',
            'type'       => 'Allow',
            'assertions' => 'Ownership',
        ]);

        self::assertTrue($result->valid());
        self::assertSame(
            [
                'resourceId' => 'dashboard',
                'type'       => RuleType::Allow,
                'roleId'     => 'Admin',
                'assertions' => ['Ownership'],
            ],
            $result->value(),
        );
    }

    #[Test]
    public function invalidTypeValueIsRejected(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'Grant']);

        self::assertFalse($result->valid());
    }

    #[Test]
    public function lowercaseTypeValueIsRejected(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'deny']);

        self::assertFalse($result->valid());
    }

    #[Test]
    public function missingResourceIdIsInvalid(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate(['roleId' => 'Admin', 'type' => 'Allow']);

        self::assertFalse($result->valid());
    }

    #[Test]
    public function missingRoleIdIsInvalid(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate(['resourceId' => 'dashboard', 'type' => 'Allow']);

        self::assertFalse($result->valid());
    }

    #[Test]
    public function missingTypeIsInvalid(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate(['roleId' => 'Admin', 'resourceId' => 'dashboard']);

        self::assertFalse($result->valid());
    }

    #[Test]
    public function passesThroughArrayAssertions(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate([
            'roleId'     => 'Admin',
            'resourceId' => 'dashboard',
            'type'       => 'Allow',
            'assertions' => ['Ownership'],
        ]);

        self::assertTrue($result->valid());
        self::assertSame(['Ownership'], $result->value()['assertions']);
    }

    #[Test]
    public function trimsResourceId(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate([
            'roleId'     => 'Admin',
            'resourceId' => ' dashboard ',
            'type'       => 'Allow',
        ]);

        self::assertTrue($result->valid());
        self::assertSame('dashboard', $result->value()['resourceId']);
    }

    #[Test]
    public function unknownAssertionIsRejected(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $result = $filter->validate([
            'roleId'     => 'Admin',
            'resourceId' => 'dashboard',
            'type'       => 'Allow',
            'assertions' => ['NotARealAssertion'],
        ]);

        self::assertFalse($result->valid());
    }
}

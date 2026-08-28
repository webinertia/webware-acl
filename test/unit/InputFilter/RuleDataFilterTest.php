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
        $filter->setValidationGroup(['roleId', 'resourceId', 'type', 'assertions']);
        $filter->setData(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'Allow']);

        self::assertTrue($filter->isValid());
        self::assertSame(
            [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'type'       => RuleType::Allow,
                'assertions' => null,
            ],
            $filter->getValues(),
        );
    }

    #[Test]
    public function denyTypeIsConvertedToEnum(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $filter->setValidationGroup(['roleId', 'resourceId', 'type']);
        $filter->setData(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'Deny']);

        self::assertTrue($filter->isValid());
        self::assertSame(RuleType::Deny, $filter->getValues()['type']);
    }

    #[Test]
    public function filtersAndNormalizesRuleData(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $filter->setValidationGroup(['roleId', 'resourceId', 'type', 'assertions']);
        $filter->setData([
            'roleId'     => ' Admin ',
            'resourceId' => 'dashboard',
            'type'       => 'Allow',
            'assertions' => 'Ownership',
        ]);

        self::assertTrue($filter->isValid());
        self::assertSame(
            [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'type'       => RuleType::Allow,
                'assertions' => ['Ownership'],
            ],
            $filter->getValues(),
        );
    }

    #[Test]
    public function invalidTypeValueIsRejected(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $filter->setValidationGroup(['roleId', 'resourceId', 'type']);
        $filter->setData(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'Grant']);

        self::assertFalse($filter->isValid());
    }

    #[Test]
    public function lowercaseTypeValueIsRejected(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $filter->setValidationGroup(['roleId', 'resourceId', 'type']);
        $filter->setData(['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => 'deny']);

        self::assertFalse($filter->isValid());
    }

    #[Test]
    public function missingTypeIsInvalid(): void
    {
        $filter = InputFilterHelper::ruleDataFilter();
        $filter->setValidationGroup(['roleId', 'resourceId', 'type']);
        $filter->setData(['roleId' => 'Admin', 'resourceId' => 'dashboard']);

        self::assertFalse($filter->isValid());
    }
}

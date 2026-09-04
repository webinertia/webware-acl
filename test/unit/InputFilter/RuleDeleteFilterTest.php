<?php

declare(strict_types=1);

namespace WebwareTest\Acl\InputFilter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\InputFilter\RuleDeleteFilter;
use WebwareTest\Acl\Support\InputFilterHelper;

#[CoversClass(RuleDeleteFilter::class)]
final class RuleDeleteFilterTest extends TestCase
{
    #[Test]
    public function filtersAndNormalizesDeleteData(): void
    {
        $filter = InputFilterHelper::ruleDeleteFilter();
        $result = $filter->validate([
            'roleId'     => ' Admin ',
            'resourceId' => ' dashboard ',
        ]);

        self::assertTrue($result->valid());
        self::assertSame(
            ['roleId' => 'Admin', 'resourceId' => 'dashboard'],
            $result->value(),
        );
    }

    #[Test]
    public function missingResourceIdIsInvalid(): void
    {
        $filter = InputFilterHelper::ruleDeleteFilter();
        $result = $filter->validate(['roleId' => 'Admin']);

        self::assertFalse($result->valid());
    }

    #[Test]
    public function missingRoleIdIsInvalid(): void
    {
        $filter = InputFilterHelper::ruleDeleteFilter();
        $result = $filter->validate(['resourceId' => 'dashboard']);

        self::assertFalse($result->valid());
    }
}

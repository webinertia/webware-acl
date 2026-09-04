<?php

declare(strict_types=1);

namespace WebwareTest\Acl\InputFilter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\InputFilter\RoleDataFilter;
use WebwareTest\Acl\Support\InputFilterHelper;

#[CoversClass(RoleDataFilter::class)]
final class RoleDataFilterTest extends TestCase
{
    #[Test]
    public function absentOptionalFieldsFallbackToNull(): void
    {
        $filter = InputFilterHelper::roleDataFilter();
        $result = $filter->validate(['roleId' => 'Guest']);

        self::assertTrue($result->valid());
        self::assertSame(
            ['id' => null, 'roleId' => 'Guest', 'parentId' => null],
            $result->value(),
        );
    }

    #[Test]
    public function filtersAndNormalizesRoleData(): void
    {
        $filter = InputFilterHelper::roleDataFilter();
        $result = $filter->validate([
            'id'       => '7',
            'roleId'   => ' Editor ',
            'parentId' => 'Admin',
        ]);

        self::assertTrue($result->valid());
        self::assertSame(
            ['id' => 7, 'roleId' => 'Editor', 'parentId' => ['Admin']],
            $result->value(),
        );
    }

    #[Test]
    public function missingRoleIdIsInvalid(): void
    {
        $filter = InputFilterHelper::roleDataFilter();
        $result = $filter->validate(['parentId' => 'Admin']);

        self::assertFalse($result->valid());
    }

    #[Test]
    public function passesThroughArrayParentId(): void
    {
        $filter = InputFilterHelper::roleDataFilter();
        $result = $filter->validate([
            'roleId'   => 'Guest',
            'parentId' => ['Admin'],
        ]);

        self::assertTrue($result->valid());
        self::assertSame(['Admin'], $result->value()['parentId']);
    }

    #[Test]
    public function trimsAndWrapsWhitespaceParentId(): void
    {
        $filter = InputFilterHelper::roleDataFilter();
        $result = $filter->validate([
            'roleId'   => 'Guest',
            'parentId' => ' Admin ',
        ]);

        self::assertTrue($result->valid());
        self::assertSame(['Admin'], $result->value()['parentId']);
    }
}

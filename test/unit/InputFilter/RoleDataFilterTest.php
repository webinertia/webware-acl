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
        $filter->setValidationGroup(['id', 'roleId', 'parentId']);
        $filter->setData(['roleId' => 'Guest']);

        self::assertTrue($filter->isValid());
        self::assertSame(
            ['id' => null, 'roleId' => 'Guest', 'parentId' => null],
            $filter->getValues(),
        );
    }

    #[Test]
    public function filtersAndNormalizesRoleData(): void
    {
        $filter = InputFilterHelper::roleDataFilter();
        $filter->setValidationGroup(['id', 'roleId', 'parentId']);
        $filter->setData([
            'id'       => '7',
            'roleId'   => ' Editor ',
            'parentId' => 'Admin',
        ]);

        self::assertTrue($filter->isValid());
        self::assertSame(
            ['id' => 7, 'roleId' => 'Editor', 'parentId' => ['Admin']],
            $filter->getValues(),
        );
    }

    #[Test]
    public function missingRoleIdIsInvalid(): void
    {
        $filter = InputFilterHelper::roleDataFilter();
        $filter->setValidationGroup(['id', 'roleId', 'parentId']);
        $filter->setData(['parentId' => 'Admin']);

        self::assertFalse($filter->isValid());
    }
}

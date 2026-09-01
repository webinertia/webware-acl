<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Ddl\Column;

use PhpDb\Sql\Argument\Identifier;
use PhpDb\Sql\Argument\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Ddl\Column\Enum;

#[CoversClass(Enum::class)]
final class EnumTest extends TestCase
{
    #[Test]
    public function constructorDefaultsToNotNullable(): void
    {
        $enum = new Enum(
            name: 'type',
            values: ['Allow', 'Deny'],
        );

        self::assertFalse($enum->isNullable());
    }

    #[Test]
    public function getExpressionDataRendersEnumTypeWithDefaultAndNotNull(): void
    {
        $enum = new Enum(
            name    : 'type',
            values  : ['Allow', 'Deny'],
            nullable: false,
            default : 'Allow',
        );

        $data = $enum->getExpressionData();

        self::assertStringContainsString("ENUM('Allow','Deny')", $data['spec']);
        self::assertStringContainsString('NOT NULL', $data['spec']);
        self::assertStringContainsString('DEFAULT', $data['spec']);
        self::assertCount(2, $data['values']);
        self::assertInstanceOf(Identifier::class, $data['values'][0]);
        self::assertSame('type', $data['values'][0]->getValue());
        self::assertInstanceOf(Value::class, $data['values'][1]);
        self::assertSame('Allow', $data['values'][1]->getValue());
    }

    #[Test]
    public function getExpressionDataRendersNullableEnumWithoutDefault(): void
    {
        $enum = new Enum(
            name    : 'type',
            values  : ['Allow', 'Deny'],
            nullable: true,
        );

        $data = $enum->getExpressionData();

        self::assertStringContainsString("ENUM('Allow','Deny')", $data['spec']);
        self::assertStringContainsString('NULL', $data['spec']);
        self::assertStringContainsString('DEFAULT NULL', $data['spec']);
        self::assertCount(1, $data['values']);
        self::assertInstanceOf(Identifier::class, $data['values'][0]);
        self::assertSame('type', $data['values'][0]->getValue());
    }
}

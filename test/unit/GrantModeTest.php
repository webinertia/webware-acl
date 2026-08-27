<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\GrantMode;

#[CoversClass(GrantMode::class)]
final class GrantModeTest extends TestCase
{
    #[Test]
    public function casesAreStrings(): void
    {
        self::assertContainsOnlyString([
            GrantMode::None->value,
            GrantMode::Explicit->value,
            GrantMode::Inherited->value,
        ]);
    }

    #[Test]
    public function explicitCaseValue(): void
    {
        self::assertSame('explicit', GrantMode::Explicit->value);
    }

    #[Test]
    public function inheritedCaseValue(): void
    {
        self::assertSame('inherited', GrantMode::Inherited->value);
    }

    #[Test]
    public function noneCaseValue(): void
    {
        self::assertSame('none', GrantMode::None->value);
    }
}

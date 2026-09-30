<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Seed;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\SeedResult;

#[CoversClass(SeedResult::class)]
#[CoversMethod(SeedResult::class, '__construct')]
#[CoversMethod(SeedResult::class, 'hasViolations')]
final class SeedResultTest extends TestCase
{
    #[Test]
    public function defaultsToNothingSeededAndNothingWrong(): void
    {
        $result = new SeedResult();

        self::assertSame(0, $result->seeded);
        self::assertFalse($result->tableMissing);
        self::assertSame([], $result->violations);
        self::assertFalse($result->hasViolations());
    }

    #[Test]
    public function reportsViolations(): void
    {
        $result = new SeedResult(
            seeded: 4,
            violations: ['something is wrong'],
        );

        self::assertSame(4, $result->seeded);
        self::assertSame(['something is wrong'], $result->violations);
        self::assertTrue($result->hasViolations());
    }
}

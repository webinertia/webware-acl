<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Seed;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\RuleSeedCollector;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Acl\RuleType;

#[CoversClass(RuleSeedCollector::class)]
#[CoversMethod(RuleSeedCollector::class, '__construct')]
#[CoversMethod(RuleSeedCollector::class, 'collect')]
final class RuleSeedCollectorTest extends TestCase
{
    #[Test]
    public function collectsEveryProvidersSeedsInOrder(): void
    {
        $first = new RuleSeed(
            type      : RuleType::Allow,
            roleId    : 'Guest',
            resourceId: 'a',
        );
        $second = new RuleSeed(
            type      : RuleType::Deny,
            roleId    : 'Member',
            resourceId: 'b',
        );
        $third = new RuleSeed(
            type      : RuleType::Allow,
            roleId    : 'Developer',
            resourceId: 'c',
        );

        $one = $this->createMock(RuleSeedProviderInterface::class);
        $one->expects($this->once())->method('ruleSeeds')->with('admin')->willReturn([$first, $second]);

        $two = $this->createMock(RuleSeedProviderInterface::class);
        $two->expects($this->once())->method('ruleSeeds')->with('admin')->willReturn([$third]);

        self::assertSame(
            [$first, $second, $third],
            new RuleSeedCollector([$one, $two], 'admin')->collect(),
        );
    }

    #[Test]
    public function collectsNothingWithoutProviders(): void
    {
        self::assertSame([], new RuleSeedCollector([], 'admin')->collect());
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Seed;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\SeedRunner;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Acl\RuleType;
use WebwareTest\Acl\Support\SeedRunnerTrait;

#[CoversClass(SeedRunner::class)]
#[CoversMethod(SeedRunner::class, '__construct')]
#[CoversMethod(SeedRunner::class, 'run')]
final class SeedRunnerTest extends TestCase
{
    use SeedRunnerTrait;

    #[Test]
    public function checksSeedsAgainstTheRegisteredRouteNames(): void
    {
        $result = $this->seedRunner(
            providers : [$this->provider(
                resourceId      : 'user.session.read',
                parentResourceId: 'user',
            )],
            routeNames: ['admin.acl'],
            tables    : ['acl_rule'],
        )->run();

        self::assertTrue($result->hasViolations());
        self::assertSame(0, $result->seeded);
    }

    #[Test]
    public function reportsAMissingTableWhenTheSetIsValid(): void
    {
        $result = $this->seedRunner(
            providers : [$this->provider(resourceId: 'admin.acl')],
            routeNames: ['admin.acl'],
            tables    : [],
        )->run();

        self::assertTrue($result->tableMissing);
        self::assertFalse($result->hasViolations());
    }

    #[Test]
    public function seedsNothingWhenNoProviderPublishesAnything(): void
    {
        $result = $this->seedRunner(tables: ['acl_rule'])->run();

        self::assertFalse($result->tableMissing);
        self::assertFalse($result->hasViolations());
        self::assertSame(0, $result->seeded);
    }

    private function provider(string $resourceId, ?string $parentResourceId = null): RuleSeedProviderInterface
    {
        $provider = $this->createStub(RuleSeedProviderInterface::class);
        $provider->method('ruleSeeds')
            ->willReturn([
                new RuleSeed(
                    type            : RuleType::Allow,
                    roleId          : 'Guest',
                    resourceId      : $resourceId,
                    parentResourceId: $parentResourceId,
                ),
            ]);

        return $provider;
    }
}

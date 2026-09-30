<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Seed;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\RuleSeeder;
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;

/**
 * Covers the paths that decide whether anything is written. The write path itself
 * needs a real connection and lives in the integration suite.
 */
#[CoversClass(RuleSeeder::class)]
#[CoversMethod(RuleSeeder::class, '__construct')]
#[CoversMethod(RuleSeeder::class, 'ruleTableExists')]
#[CoversMethod(RuleSeeder::class, 'seed')]
final class RuleSeederTest extends TestCase
{
    /** @var list<string> */
    private const array ROUTES = ['admin.acl', 'user.session.read'];

    #[Test]
    public function reportsAMissingTableWithoutSeeding(): void
    {
        $result = $this->seeder(tables: [])->seed(
            ruleSeeds : [$this->seed()],
            routeNames: self::ROUTES,
        );

        self::assertTrue($result->tableMissing);
        self::assertSame(0, $result->seeded);
        self::assertSame([], $result->violations);
    }

    #[Test]
    public function reportsWhetherTheTableIsPresent(): void
    {
        self::assertTrue($this->seeder(tables: ['acl_rule'])->ruleTableExists());
        self::assertFalse($this->seeder(tables: [])->ruleTableExists());
    }

    #[Test]
    public function writesNothingWhenTheSetIsInvalid(): void
    {
        $result = $this->seeder(tables: ['acl_rule'])->seed(
            ruleSeeds : [$this->seed(
                resourceId      : 'user.manager.session.read',
                parentResourceId: 'user',
            )],
            routeNames: self::ROUTES,
        );

        self::assertTrue($result->hasViolations());
        self::assertSame(0, $result->seeded);
        self::assertFalse($result->tableMissing);
    }

    private function seed(string $resourceId = 'user.session.read', ?string $parentResourceId = null): RuleSeed
    {
        return new RuleSeed(
            type            : RuleType::Allow,
            roleId          : 'Guest',
            resourceId      : $resourceId,
            parentResourceId: $parentResourceId,
        );
    }

    /**
     * @param list<string> $tables
     */
    private function seeder(array $tables): RuleSeeder
    {
        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getTableNames')->willReturn($tables);

        return new RuleSeeder(
            adapter  : $this->createStub(AdapterInterface::class),
            table    : new TableIdentifier(table: 'acl_rule'),
            metadata : $metadata,
            validator: new RuleSeedValidator(),
        );
    }
}

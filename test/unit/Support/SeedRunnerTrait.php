<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Support;

use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Sql\TableIdentifier;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Console\Seed\RuleSeedCollector;
use Webware\Acl\Console\Seed\RuleSeeder;
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Acl\Console\Seed\SeedRunner;
use Webware\Core\Acl\RuleSeedProviderInterface;

/**
 * Builds a real {@see SeedRunner} over stubbed edges.
 *
 * The runner and everything it composes are final, so a test cannot stub them;
 * it assembles the real objects and stubs the route collector, the metadata and
 * the adapter instead. An absent rule table keeps the stubbed adapter from ever
 * being asked to write.
 */
trait SeedRunnerTrait
{
    /**
     * @param list<RuleSeedProviderInterface> $providers
     * @param list<string>                    $routeNames
     * @param list<string>                    $tables
     */
    private function seedRunner(array $providers = [], array $routeNames = [], array $tables = []): SeedRunner
    {
        $routes = [];

        foreach ($routeNames as $name) {
            $routes[] = new Route("/{$name}", $this->createStub(MiddlewareInterface::class), ['GET'], $name);
        }

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')->willReturn($routes);

        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getTableNames')->willReturn($tables);

        return new SeedRunner(
            collector     : new RuleSeedCollector($providers, 'admin'),
            seeder        : new RuleSeeder(
                adapter  : $this->createStub(AdapterInterface::class),
                table    : new TableIdentifier(table: 'acl_rule'),
                metadata : $metadata,
                validator: new RuleSeedValidator(),
            ),
            routeCollector: $routeCollector,
        );
    }
}

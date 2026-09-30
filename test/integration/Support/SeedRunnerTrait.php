<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl\Support;

use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Mysql\Metadata\Source;
use PhpDb\Sql\TableIdentifier;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Acl\RuleSeeds;
use Webware\Acl\Console\Seed\RuleSeedCollector;
use Webware\Acl\Console\Seed\RuleSeeder;
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Acl\Console\Seed\SeedRunner;

/**
 * A real seeding stack over a live adapter.
 *
 * The route collector is stubbed with the seeded names: the invariant that
 * those names are the routes RouteProvider registers is asserted in the unit
 * suite, so the integration suite only needs the write path to be reachable.
 */
trait SeedRunnerTrait
{
    private function seedRunner(AdapterInterface $adapter): SeedRunner
    {
        $provider = new RuleSeeds();
        $routes   = [];

        foreach ($provider->ruleSeeds('admin') as $seed) {
            $routes[] = new Route(
                "/{$seed->resourceId}",
                $this->createStub(MiddlewareInterface::class),
                ['GET'],
                $seed->resourceId,
            );
        }

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')->willReturn($routes);

        return new SeedRunner(
            collector     : new RuleSeedCollector([$provider], 'admin'),
            seeder        : new RuleSeeder(
                adapter  : $adapter,
                table    : new TableIdentifier(table: 'acl_rule'),
                metadata : new Source($adapter),
                validator: new RuleSeedValidator(),
            ),
            routeCollector: $routeCollector,
        );
    }
}

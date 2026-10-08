<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Seed;

use JsonException;
use Mezzio\Router\RouteCollectorInterface;
use PhpDb\Exception\ExceptionInterface as PhpDbException;

/**
 * Collects every provider's seeds and writes them against the registered routes.
 *
 * @internal
 */
final readonly class SeedRunner
{
    public function __construct(
        private RuleSeedCollector $collector,
        private RuleSeeder $seeder,
        private RouteCollectorInterface $routeCollector,
    ) {}

    /**
     * @throws JsonException
     * @throws PhpDbException
     */
    public function run(): SeedResult
    {
        $routeNames = [];

        foreach ($this->routeCollector->getRoutes() as $route) {
            $routeNames[] = $route->getName();
        }

        return $this->seeder->seed(
            ruleSeeds : $this->collector->collect(),
            routeNames: $routeNames,
        );
    }
}

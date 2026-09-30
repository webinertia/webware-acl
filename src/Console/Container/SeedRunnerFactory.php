<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Container;

use Mezzio\Router\RouteCollectorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Console\Seed\RuleSeedCollector;
use Webware\Acl\Console\Seed\RuleSeeder;
use Webware\Acl\Console\Seed\SeedRunner;

final readonly class SeedRunnerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SeedRunner
    {
        return new SeedRunner(
            collector     : $container->get(RuleSeedCollector::class),
            seeder        : $container->get(RuleSeeder::class),
            routeCollector: $container->get(RouteCollectorInterface::class),
        );
    }
}

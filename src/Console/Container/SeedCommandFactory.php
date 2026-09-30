<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Exception\LogicException;
use Webware\Acl\Console\Seed\SeedRunner;
use Webware\Acl\Console\SeedCommand;

final readonly class SeedCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws LogicException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): SeedCommand
    {
        return new SeedCommand(runner: $container->get(SeedRunner::class));
    }
}

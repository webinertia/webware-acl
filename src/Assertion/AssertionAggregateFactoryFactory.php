<?php

declare(strict_types=1);

namespace Webware\Acl\Assertion;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\AssertionManager;

final readonly class AssertionAggregateFactoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AssertionAggregateFactory
    {
        $assertionManager = $container->get(AssertionManager::class);

        return new AssertionAggregateFactory($assertionManager);
    }
}

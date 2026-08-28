<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\Middleware\Container;

use Mezzio\Router\RouteCollectorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\AssertionManager;
use Webware\Acl\Http\Admin\Middleware\OverviewMiddleware;
use Webware\MessageBus\MessageBusInterface;

final class OverviewMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): OverviewMiddleware
    {
        return new OverviewMiddleware(
            $container->get(MessageBusInterface::class),
            $container->get(RouteCollectorInterface::class),
            $container->get(AssertionManager::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\Middleware\RoleListMiddleware;
use Webware\MessageBus\MessageBusInterface;

final class RoleListMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RoleListMiddleware
    {
        return new RoleListMiddleware(
            $container->get(MessageBusInterface::class),
        );
    }
}

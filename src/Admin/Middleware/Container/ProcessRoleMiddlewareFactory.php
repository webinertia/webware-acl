<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\MessageBus\MessageBusInterface;

final class ProcessRoleMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessRoleMiddleware
    {
        return new ProcessRoleMiddleware(
            $container->get(MessageBusInterface::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\Middleware\AddRoleModalMiddleware;
use Webware\MessageBus\MessageBusInterface;

final class AddRoleModalMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AddRoleModalMiddleware
    {
        return new AddRoleModalMiddleware(
            $container->get(MessageBusInterface::class),
        );
    }
}

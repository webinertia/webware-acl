<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\Middleware\EditRoleModalMiddleware;
use Webware\MessageBus\MessageBusInterface;

final class EditRoleModalMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): EditRoleModalMiddleware
    {
        return new EditRoleModalMiddleware(
            $container->get(MessageBusInterface::class),
        );
    }
}

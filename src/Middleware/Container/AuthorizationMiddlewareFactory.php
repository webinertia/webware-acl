<?php

declare(strict_types=1);

namespace Webware\Acl\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\RouteResourceFactoryInterface;
use Webware\Acl\Middleware\AuthorizationMiddleware;
use Webware\Acl\RequestHandler\ForbiddenHandlerInterface;

final class AuthorizationMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AuthorizationMiddleware
    {
        return new AuthorizationMiddleware(
            $container->get(ForbiddenHandlerInterface::class),
            $container->get(RouteResourceFactoryInterface::class),
        );
    }
}

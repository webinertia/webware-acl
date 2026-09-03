<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Http\Middleware\AuthorizationMiddleware;
use Webware\Acl\Http\RequestHandler\ForbiddenHandlerInterface;
use Webware\Acl\Http\RouteResourceFactoryInterface;

final class AuthorizationMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AuthorizationMiddleware
    {
        /** @var ForbiddenHandlerInterface&RequestHandlerInterface $forbiddenHandler */
        $forbiddenHandler = $container->get(ForbiddenHandlerInterface::class);

        return new AuthorizationMiddleware(
            $forbiddenHandler,
            $container->get(RouteResourceFactoryInterface::class),
        );
    }
}

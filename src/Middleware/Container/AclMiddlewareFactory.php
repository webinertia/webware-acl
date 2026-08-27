<?php

declare(strict_types=1);

namespace Webware\Acl\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Middleware\AclMiddleware;
use Webware\Core\AclInterface;

final class AclMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AclMiddleware
    {
        return new AclMiddleware(
            $container->get(AclInterface::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\RouteResourceFactory;
use Webware\Core\AclInterface;

final class RouteResourceFactoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RouteResourceFactory
    {
        /** @var array<string, array<string, mixed>> $paramMap */
        $paramMap = $container->get('config')[AclInterface::class]['route_param_map'] ?? [];

        return new RouteResourceFactory($paramMap);
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\QueryHandler\FetchAllRolesHandler;
use Webware\Acl\Repository\RoleRepository;

final readonly class FetchAllRolesHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchAllRolesHandler
    {
        return new FetchAllRolesHandler(
            $container->get(RoleRepository::class),
        );
    }
}

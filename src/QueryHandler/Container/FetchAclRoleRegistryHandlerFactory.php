<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\QueryHandler\FetchAclRoleRegistryHandler;
use Webware\Acl\Repository\RoleRepository;

final readonly class FetchAclRoleRegistryHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchAclRoleRegistryHandler
    {
        return new FetchAclRoleRegistryHandler(
            $container->get(RoleRepository::class),
        );
    }
}

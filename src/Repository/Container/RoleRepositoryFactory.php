<?php

declare(strict_types=1);

namespace Webware\Acl\Repository\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Exception\ExceptionInterface as PhpDbException;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\SchemaFactory;
use PhpDb\TableGateway\TableGateway;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Entity\Role;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\Schema;

final class RoleRepositoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws PhpDbException
     * @throws PslTypeException
     */
    public function __invoke(ContainerInterface $container): RoleRepository
    {
        return new RoleRepository(
            new TableGateway(
                table             : $container->get(SchemaFactory::class)(Schema::Roles),
                adapter           : $container->get(AdapterInterface::class),
                resultSetPrototype: new RowPrototypeResultSet(
                    rowPrototype: new Role(),
                ),
            ),
        );
    }
}

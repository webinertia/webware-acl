<?php

declare(strict_types=1);

namespace Webware\Acl\Repository\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\TableGateway\Exception\ExceptionInterface as TableGatewayException;
use PhpDb\TableGateway\TableGateway;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Entity\Rule;
use Webware\Acl\Repository\RuleRepository;
use Webware\Acl\Repository\Schema;
use Webware\Core\SchemaFactory;

final class RuleRepositoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SqlException
     * @throws TableGatewayException
     * @throws PslTypeException
     */
    public function __invoke(ContainerInterface $container): RuleRepository
    {
        return new RuleRepository(
            new TableGateway(
                table             : $container->get(SchemaFactory::class)(Schema::Rules),
                adapter           : $container->get(AdapterInterface::class),
                resultSetPrototype: new RowPrototypeResultSet(
                    rowPrototype: new Rule(),
                ),
            ),
        );
    }
}

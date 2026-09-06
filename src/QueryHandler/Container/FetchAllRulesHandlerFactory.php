<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\ResultSet\ArrayResultSet;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\TableGateway\Exception\ExceptionInterface as TableGatewayException;
use PhpDb\TableGateway\TableGateway;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\QueryHandler\FetchAllRulesHandler;
use Webware\Acl\Repository\Schema;
use Webware\Core\SchemaFactory;

final readonly class FetchAllRulesHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SqlException
     * @throws TableGatewayException
     * @throws PslTypeException
     */
    public function __invoke(ContainerInterface $container): FetchAllRulesHandler
    {
        return new FetchAllRulesHandler(
            new TableGateway(
                table             : $container->get(SchemaFactory::class)(Schema::Rules),
                adapter           : $container->get(AdapterInterface::class),
                resultSetPrototype: new ArrayResultSet(),
            ),
        );
    }
}

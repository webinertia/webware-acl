<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Exception\ExceptionInterface as PhpDbException;
use PhpDb\ResultSet\ArrayResultSet;
use PhpDb\SchemaFactory;
use PhpDb\TableGateway\TableGateway;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\QueryHandler\FetchDistinctResourceIdsHandler;
use Webware\Acl\Repository\Schema;

final readonly class FetchDistinctResourceIdsHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws PhpDbException
     * @throws PslTypeException
     */
    public function __invoke(ContainerInterface $container): FetchDistinctResourceIdsHandler
    {
        return new FetchDistinctResourceIdsHandler(
            new TableGateway(
                table             : $container->get(SchemaFactory::class)(Schema::Rules),
                adapter           : $container->get(AdapterInterface::class),
                resultSetPrototype: new ArrayResultSet(),
            ),
        );
    }
}

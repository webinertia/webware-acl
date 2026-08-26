<?php

declare(strict_types=1);

namespace Webware\Acl\Repository\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\TableGateway\Exception\ExceptionInterface as TableGatewayException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Repository\RuleRepository;

final class RuleRepositoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SqlException
     * @throws TableGatewayException
     */
    public function __invoke(ContainerInterface $container): RuleRepository
    {
        return new RuleRepository(
            $container->get(AdapterInterface::class),
        );
    }
}

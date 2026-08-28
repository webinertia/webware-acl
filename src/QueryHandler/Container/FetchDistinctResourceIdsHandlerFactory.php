<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\QueryHandler\FetchDistinctResourceIdsHandler;
use Webware\Acl\Repository\RuleRepository;

final readonly class FetchDistinctResourceIdsHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchDistinctResourceIdsHandler
    {
        return new FetchDistinctResourceIdsHandler(
            $container->get(RuleRepository::class),
        );
    }
}

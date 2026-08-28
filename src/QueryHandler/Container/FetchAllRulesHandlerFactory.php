<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\QueryHandler\FetchAllRulesHandler;
use Webware\Acl\Repository\RuleRepository;

final readonly class FetchAllRulesHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): FetchAllRulesHandler
    {
        return new FetchAllRulesHandler(
            $container->get(RuleRepository::class),
        );
    }
}

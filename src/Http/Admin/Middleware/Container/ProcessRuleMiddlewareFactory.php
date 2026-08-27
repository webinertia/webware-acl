<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\Middleware\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\Middleware\ProcessRuleMiddleware;
use Webware\MessageBus\MessageBusInterface;

final class ProcessRuleMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ProcessRuleMiddleware
    {
        return new ProcessRuleMiddleware(
            $container->get(MessageBusInterface::class),
        );
    }
}

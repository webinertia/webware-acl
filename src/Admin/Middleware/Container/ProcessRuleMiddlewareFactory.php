<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Laminas\inputFilter\Factory;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;
use Webware\MessageBus\MessageBusInterface;

final class ProcessRuleMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessRuleMiddleware
    {
        return new ProcessRuleMiddleware(
            $container->get(MessageBusInterface::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\RequestHandler\RoleListHandler;
use Webware\MessageBus\MessageBusInterface;

final class RoleListHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RoleListHandler
    {
        return new RoleListHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MessageBusInterface::class),
        );
    }
}

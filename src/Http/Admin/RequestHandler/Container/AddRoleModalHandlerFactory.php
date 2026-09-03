<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\RequestHandler\AddRoleModalHandler;
use Webware\MessageBus\MessageBusInterface;

final class AddRoleModalHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AddRoleModalHandler
    {
        return new AddRoleModalHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(MessageBusInterface::class),
        );
    }
}

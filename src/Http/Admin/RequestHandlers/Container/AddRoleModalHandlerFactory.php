<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandlers\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\RequestHandlers\AddRoleModalHandler;
use Webware\Acl\Repository\RoleRepository;

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
            $container->get(RoleRepository::class),
        );
    }
}

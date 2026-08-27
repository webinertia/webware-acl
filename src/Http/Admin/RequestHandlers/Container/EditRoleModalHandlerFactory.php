<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandlers\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\RequestHandlers\EditRoleModalHandler;
use Webware\Acl\Repository\RoleRepository;

final class EditRoleModalHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): EditRoleModalHandler
    {
        return new EditRoleModalHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(RoleRepository::class),
        );
    }
}

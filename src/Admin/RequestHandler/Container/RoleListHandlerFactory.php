<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\Repository\RoleRepository;

final class RoleListHandlerFactory
{
    public function __invoke(ContainerInterface $container): RoleListHandler
    {
        return new RoleListHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(RoleRepository::class),
        );
    }
}

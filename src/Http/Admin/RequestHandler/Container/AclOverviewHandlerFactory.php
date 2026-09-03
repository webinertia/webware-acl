<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\RequestHandler\AclOverviewHandler;

final class AclOverviewHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AclOverviewHandler
    {
        return new AclOverviewHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}

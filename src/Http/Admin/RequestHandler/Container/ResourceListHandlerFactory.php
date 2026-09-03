<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\RequestHandler\ResourceListHandler;
use Webware\Core\AclInterface;

final class ResourceListHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceListHandler
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config');

        return new ResourceListHandler(
            $config[AclInterface::class] ?? [],
            $container->get(TemplateRendererInterface::class),
        );
    }
}

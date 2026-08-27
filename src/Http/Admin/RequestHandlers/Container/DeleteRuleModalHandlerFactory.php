<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandlers\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Http\Admin\RequestHandlers\DeleteRuleModalHandler;

final class DeleteRuleModalHandlerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): DeleteRuleModalHandler
    {
        return new DeleteRuleModalHandler(
            $container->get(TemplateRendererInterface::class),
        );
    }
}

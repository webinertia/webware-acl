<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Dashboard\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Admin\Dashboard\RegisterWidgetListener;
use Webware\Acl\Container\Configuration;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\Core\AclInterface;

use function rtrim;

final readonly class RegisterWidgetListenerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RegisterWidgetListener
    {
        $resourceId = rtrim(
            string    : Configuration::getAdminRouteNamePrefix(
                AdminConfiguration::getAdminName($container, self::class),
            ),
            characters: '.',
        );

        /** @var array<string, mixed> $config */
        $config = $container->get('config');

        return new RegisterWidgetListener(
            $resourceId,
            $config[AclInterface::class] ?? [],
        );
    }
}

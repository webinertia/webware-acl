<?php

declare(strict_types=1);

namespace Webware\Acl\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\RouteProvider;
use Webware\Admin\Container\Configuration as AdminConfiguration;

final readonly class RouteProviderFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RouteProvider
    {
        $adminName = AdminConfiguration::getAdminName($container, self::class);

        // This module's admin routes nest under the admin namespace: names 'admin.acl.'
        // and the 'admin/acl' segment, both derived from the resolved base so an
        // application that relocates the namespace moves them with it.
        return new RouteProvider(
            Configuration::getAdminRouteSegment($adminName),
            Configuration::getAdminRouteNamePrefix($adminName),
        );
    }
}

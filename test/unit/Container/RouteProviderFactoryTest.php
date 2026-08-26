<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\Acl\AclInterface;
use Webware\Acl\Container\RouteProviderFactory;
use Webware\Acl\RouteProvider;
use Webware\Admin\Container\Configuration as AdminConfiguration;

#[CoversClass(RouteProviderFactory::class)]
final class RouteProviderFactoryTest extends TestCase
{
    #[Test]
    public function invokeCombinesAdminAndModuleRouteSegments(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container
            ->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AdminConfiguration::CONFIG_KEY => [
                            'admin_route_segment'     => 'admin',
                            'admin_route_name_prefix' => 'admin.',
                        ],
                        AclInterface::class            => [
                            'admin_route_segment'     => 'acl',
                            'admin_route_name_prefix' => 'acl.',
                        ],
                    ],
                ],
            ]);

        $provider = (new RouteProviderFactory())($container);

        self::assertSame('admin/acl', $this->readProperty($provider, 'adminRouteSegment'));
        self::assertSame('admin.acl.', $this->readProperty($provider, 'adminRouteNamePrefix'));
    }

    private function readProperty(RouteProvider $provider, string $name): string
    {
        $value = new ReflectionProperty(RouteProvider::class, $name)->getValue($provider);

        return $value;
    }
}

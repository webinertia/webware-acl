<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\Acl\Container\RouteProviderFactory;
use Webware\Acl\RouteProvider;
use Webware\Admin\Container\Configuration as AdminConfiguration;

#[CoversClass(RouteProviderFactory::class)]
final class RouteProviderFactoryTest extends TestCase
{
    #[Test]
    public function invokeCombinesAdminAndModuleRouteSegments(): void
    {
        $provider = new RouteProviderFactory()($this->container([]));

        self::assertSame('admin/acl', $this->readProperty($provider, 'adminRouteSegment'));
        self::assertSame('admin.acl.', $this->readProperty($provider, 'adminRouteNamePrefix'));
    }

    #[Test]
    public function invokeFollowsAConfiguredAdminNamespace(): void
    {
        $provider = new RouteProviderFactory()($this->container([
            AdminConfiguration::ADMIN_NAME_KEY => 'control-panel',
        ]));

        self::assertSame('control-panel/acl', $this->readProperty($provider, 'adminRouteSegment'));
        self::assertSame('control-panel.acl.', $this->readProperty($provider, 'adminRouteNamePrefix'));
    }

    /**
     * @param array<string, mixed> $adminConfig
     */
    private function container(array $adminConfig): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container->method('get')
            ->willReturnMap([
                ['config', [AdminConfiguration::CONFIG_KEY => $adminConfig]],
            ]);

        return $container;
    }

    private function readProperty(RouteProvider $provider, string $name): string
    {
        return new ReflectionProperty(RouteProvider::class, $name)->getValue($provider);
    }
}

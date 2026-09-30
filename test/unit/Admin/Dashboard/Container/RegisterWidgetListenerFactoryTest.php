<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Dashboard\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webware\Acl\Admin\Dashboard\Container\RegisterWidgetListenerFactory;
use Webware\Acl\Admin\Dashboard\RegisterWidgetListener;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\Core\AclInterface;

#[CoversClass(RegisterWidgetListenerFactory::class)]
final class RegisterWidgetListenerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsListener(): void
    {
        $listener = (new RegisterWidgetListenerFactory())($this->container(['acl_config' => true]));

        self::assertInstanceOf(RegisterWidgetListener::class, $listener);
        self::assertSame('admin.acl', $this->property($listener, 'resourceId'));
        self::assertSame(['acl_config' => true], $this->property($listener, 'config'));
    }

    #[Test]
    public function theResourceIdFollowsAConfiguredAdminNamespace(): void
    {
        $listener = (new RegisterWidgetListenerFactory())($this->container([], 'control-panel'));

        self::assertSame('control-panel.acl', $this->property($listener, 'resourceId'));
    }

    /**
     * @param array<string, mixed> $aclConfig
     */
    private function container(array $aclConfig, ?string $adminName = null): ContainerInterface
    {
        $adminConfig = null === $adminName ? [] : [AdminConfiguration::ADMIN_NAME_KEY => $adminName];

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AdminConfiguration::CONFIG_KEY => $adminConfig,
                        AclInterface::class            => $aclConfig,
                    ],
                ],
            ]);

        return $container;
    }

    private function property(RegisterWidgetListener $listener, string $name): mixed
    {
        return new ReflectionProperty(RegisterWidgetListener::class, $name)->getValue($listener);
    }
}

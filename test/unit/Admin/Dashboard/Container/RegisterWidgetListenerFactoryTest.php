<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Dashboard\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Admin\Dashboard\Container\RegisterWidgetListenerFactory;
use Webware\Acl\Admin\Dashboard\RegisterWidgetListener;
use Webware\Admin\Container\Configuration as AdminConfiguration;

#[CoversClass(RegisterWidgetListenerFactory::class)]
final class RegisterWidgetListenerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsListener(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['config', true]]);
        $container
            ->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AdminConfiguration::CONFIG_KEY => ['admin_route_name_prefix' => 'admin.'],
                        AclInterface::class            => ['admin_route_name_prefix' => 'acl.'],
                    ],
                ],
            ]);

        self::assertInstanceOf(RegisterWidgetListener::class, (new RegisterWidgetListenerFactory())($container));
    }
}

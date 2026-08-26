<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Http\Container\RouteResourceFactoryFactory;
use Webware\Acl\Http\RouteResourceFactory;

#[CoversClass(RouteResourceFactoryFactory::class)]
final class RouteResourceFactoryFactoryTest extends TestCase
{
    #[Test]
    public function invokeDefaultsToEmptyParamMapWhenConfigMissing(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap([['config', []]]);

        self::assertInstanceOf(RouteResourceFactory::class, (new RouteResourceFactoryFactory())($container));
    }

    #[Test]
    public function invokeReadsParamMapFromConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [
                    'config',
                    [AclInterface::class => ['route_param_map' => ['admin.users' => ['ownerId' => 'userId']]]],
                ],
            ]);

        self::assertInstanceOf(RouteResourceFactory::class, (new RouteResourceFactoryFactory())($container));
    }
}

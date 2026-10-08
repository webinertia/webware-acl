<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\Admin\Middleware\AddRoleModalMiddleware;
use Webware\Acl\Http\Admin\Middleware\Container\AddRoleModalMiddlewareFactory;
use Webware\MessageBus\MessageBusInterface;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(AddRoleModalMiddlewareFactory::class)]
final class AddRoleModalMiddlewareFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createQueryBus($this->createAdapter([]))],
            ]);

        self::assertInstanceOf(AddRoleModalMiddleware::class, (new AddRoleModalMiddlewareFactory())($container));
    }
}

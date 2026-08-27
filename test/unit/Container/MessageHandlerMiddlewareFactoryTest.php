<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Container\MessageHandlerMiddlewareFactory;
use Webware\Acl\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\Core\AclInterface;

#[CoversClass(MessageHandlerMiddlewareFactory::class)]
final class MessageHandlerMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddlewareWithAcl(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [AclInterface::class, $this->createStub(AclInterface::class)],
            ]);

        self::assertInstanceOf(MessageHandlerMiddleware::class, (new MessageHandlerMiddlewareFactory())($container));
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Middleware\AclMiddleware;
use Webware\Acl\Middleware\Container\AclMiddlewareFactory;
use Webware\Core\AclInterface;

#[CoversClass(AclMiddlewareFactory::class)]
final class AclMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [AclInterface::class, $this->createStub(AclInterface::class)],
            ]);

        self::assertInstanceOf(AclMiddleware::class, (new AclMiddlewareFactory())($container));
    }
}

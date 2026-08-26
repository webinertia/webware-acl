<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\RouteResourceFactoryInterface;
use Webware\Acl\Middleware\AuthorizationMiddleware;
use Webware\Acl\Middleware\Container\AuthorizationMiddlewareFactory;
use Webware\Acl\RequestHandler\ForbiddenHandlerInterface;

#[CoversClass(AuthorizationMiddlewareFactory::class)]
final class AuthorizationMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [ForbiddenHandlerInterface::class, $this->createStub(ForbiddenHandlerInterface::class)],
                [RouteResourceFactoryInterface::class, $this->createStub(RouteResourceFactoryInterface::class)],
            ]);

        self::assertInstanceOf(AuthorizationMiddleware::class, (new AuthorizationMiddlewareFactory())($container));
    }
}

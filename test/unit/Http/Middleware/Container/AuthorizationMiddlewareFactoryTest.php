<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Http\Middleware\AuthorizationMiddleware;
use Webware\Acl\Http\Middleware\Container\AuthorizationMiddlewareFactory;
use Webware\Acl\Http\RequestHandler\ForbiddenHandlerInterface;
use Webware\Acl\Http\RouteResourceFactoryInterface;

#[CoversClass(AuthorizationMiddlewareFactory::class)]
final class AuthorizationMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [
                    ForbiddenHandlerInterface::class,
                    $this->createStubForIntersectionOfInterfaces([
                        ForbiddenHandlerInterface::class,
                        RequestHandlerInterface::class,
                    ]),
                ],
                [RouteResourceFactoryInterface::class, $this->createStub(RouteResourceFactoryInterface::class)],
            ]);

        self::assertInstanceOf(AuthorizationMiddleware::class, (new AuthorizationMiddlewareFactory())($container));
    }
}

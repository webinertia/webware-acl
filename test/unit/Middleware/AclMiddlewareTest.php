<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Middleware\AclMiddleware;

#[CoversClass(AclMiddleware::class)]
final class AclMiddlewareTest extends TestCase
{
    #[Test]
    public function processAttachesAclAttributeAndDelegates(): void
    {
        $acl        = $this->createStub(AclInterface::class);
        $middleware = new AclMiddleware($acl);

        $decorated = $this->createStub(ServerRequestInterface::class);
        $request   = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())
            ->method('withAttribute')
            ->with(AclInterface::class, $acl)
            ->willReturn($decorated);

        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($decorated)
            ->willReturn($response);

        self::assertSame($response, $middleware->process($request, $handler));
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Middleware;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Webware\Acl\Http\Middleware\AuthorizationMiddleware;
use Webware\Acl\Http\RouteResourceFactoryInterface;
use Webware\Acl\Http\RouteResourceInterface;
use Webware\Acl\RequestHandler\ForbiddenHandlerInterface;
use Webware\Core\AclInterface;

#[CoversClass(AuthorizationMiddleware::class)]
final class AuthorizationMiddlewareTest extends TestCase
{
    #[Test]
    public function processDelegatesWhenNoRouteResult(): void
    {
        $request  = $this->request([]);
        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        self::assertSame($response, $this->middleware()->process($request, $handler));
    }

    #[Test]
    public function processDelegatesWhenRouteFailed(): void
    {
        $request  = $this->request([RouteResult::class => RouteResult::fromRouteFailure(null)]);
        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        self::assertSame($response, $this->middleware()->process($request, $handler));
    }

    #[Test]
    public function processDelegatesWhenRouteIsAllowed(): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('isAllowedRoute')->willReturn(true);

        $resource = $this->createStub(RouteResourceInterface::class);
        $factory  = $this->createStub(RouteResourceFactoryInterface::class);
        $factory->method('__invoke')->willReturn($resource);

        $request = $this->request([
            RouteResult::class  => RouteResult::fromRoute($this->route(), []),
            AclInterface::class => $acl,
        ]);
        $response = $this->createStub(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        self::assertSame(
            $response,
            new AuthorizationMiddleware(
                $this->createStub(ForbiddenHandlerInterface::class),
                $factory,
            )->process($request, $handler),
        );
    }

    #[Test]
    public function processReturnsForbiddenResponseWhenDenied(): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('isAllowedRoute')->willReturn(false);

        $resource = $this->createStub(RouteResourceInterface::class);
        $factory  = $this->createStub(RouteResourceFactoryInterface::class);
        $factory->method('__invoke')->willReturn($resource);

        $forbiddenResponse = $this->createStub(ResponseInterface::class);
        $forbidden         = $this->createStub(ForbiddenHandlerInterface::class);
        $forbidden->method('handle')->willReturn($forbiddenResponse);

        $request = $this->request([
            RouteResult::class  => RouteResult::fromRoute($this->route(), []),
            AclInterface::class => $acl,
        ]);

        self::assertSame(
            $forbiddenResponse,
            new AuthorizationMiddleware($forbidden, $factory)->process(
                $request,
                $this->createStub(RequestHandlerInterface::class),
            ),
        );
    }

    #[Test]
    public function processThrowsWhenAclAttributeMissing(): void
    {
        $request = $this->request([RouteResult::class => RouteResult::fromRoute($this->route(), [])]);

        $this->expectException(RuntimeException::class);

        $this->middleware()->process($request, $this->createStub(RequestHandlerInterface::class));
    }

    private function middleware(): AuthorizationMiddleware
    {
        return new AuthorizationMiddleware(
            $this->createStub(ForbiddenHandlerInterface::class),
            $this->createStub(RouteResourceFactoryInterface::class),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function request(array $attributes): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $name, mixed $default = null): mixed => $attributes[$name] ?? $default,
            );

        return $request;
    }

    private function route(): Route
    {
        return new Route('/', $this->createStub(MiddlewareInterface::class), null, 'admin.users');
    }
}

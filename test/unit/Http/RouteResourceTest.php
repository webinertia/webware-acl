<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http;

use Laminas\Permissions\Acl\Role\RoleInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Http\RouteResource;
use Webware\Core\UserInterface;

#[CoversClass(RouteResource::class)]
final class RouteResourceTest extends TestCase
{
    #[Test]
    public function getResourceIdReturnsMatchedRouteName(): void
    {
        $resource = new RouteResource(
            RouteResult::fromRoute($this->route('admin.users'), []),
            $this->request(),
        );

        self::assertSame('admin.users', $resource->getResourceId());
    }

    #[Test]
    public function getRoleReturnsRequestUserAttribute(): void
    {
        $user     = $this->createStub(RoleInterface::class);
        $resource = new RouteResource(
            RouteResult::fromRoute($this->route('admin.users'), []),
            $this->request(attributes: [UserInterface::class => $user]),
        );

        self::assertSame($user, $resource->getRole());
    }

    #[Test]
    public function ownerIdComesFromParamMapWhenNoRouteOption(): void
    {
        $resource = new RouteResource(
            RouteResult::fromRoute($this->route('admin.users'), ['userId' => 12]),
            $this->request(),
            ['admin.users' => ['ownerId' => 'userId']],
        );

        self::assertSame(12, $resource->getOwnerId());
    }

    #[Test]
    public function ownerIdComesFromRouteOptionsFirst(): void
    {
        $route    = $this->route('admin.users', ['acl' => ['ownerId' => 'userId']]);
        $resource = new RouteResource(
            RouteResult::fromRoute($route, ['userId' => 11]),
            $this->request(),
        );

        self::assertSame(11, $resource->getOwnerId());
    }

    #[Test]
    public function ownerIdDefaultsToZero(): void
    {
        $resource = new RouteResource(
            RouteResult::fromRoute($this->route('admin.users'), []),
            $this->request(),
        );

        self::assertSame(0, $resource->getOwnerId());
    }

    #[Test]
    public function ownerIdFallsBackToQueryParams(): void
    {
        $resource = new RouteResource(
            RouteResult::fromRoute($this->route('admin.users'), []),
            $this->request(queryParams: ['ownerId' => '22']),
        );

        self::assertSame(22, $resource->getOwnerId());
    }

    #[Test]
    public function ownerIdFallsBackToRequestAttribute(): void
    {
        $resource = new RouteResource(
            RouteResult::fromRoute($this->route('admin.users'), []),
            $this->request(attributes: ['ownerId' => '33']),
        );

        self::assertSame(33, $resource->getOwnerId());
    }

    /**
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $attributes
     */
    private function request(array $queryParams = [], array $attributes = []): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $name, mixed $default = null): mixed => $attributes[$name] ?? $default,
            );

        return $request;
    }

    private function route(string $name, array $options = []): Route
    {
        $route = new Route('/', $this->createStub(MiddlewareInterface::class), null, $name);
        $route->setOptions($options);

        return $route;
    }
}

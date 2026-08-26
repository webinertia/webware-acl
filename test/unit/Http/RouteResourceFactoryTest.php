<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Http\RouteResource;
use Webware\Acl\Http\RouteResourceFactory;

#[CoversClass(RouteResourceFactory::class)]
final class RouteResourceFactoryTest extends TestCase
{
    #[Test]
    public function invokeCreatesRouteResource(): void
    {
        $factory = new RouteResourceFactory(['admin.users' => ['ownerId' => 'userId']]);

        $resource = $factory(
            RouteResult::fromRoute($this->route('admin.users'), []),
            $this->createStub(ServerRequestInterface::class),
        );

        self::assertInstanceOf(RouteResource::class, $resource);
    }

    private function route(string $name): Route
    {
        return new Route('/', $this->createStub(MiddlewareInterface::class), null, $name);
    }
}

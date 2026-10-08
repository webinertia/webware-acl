<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\Middleware;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Http\Admin\Middleware\RoleListMiddleware;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(RoleListMiddleware::class)]
final class RoleListMiddlewareTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function attachesRolesAndTheParentMap(): void
    {
        $bus = $this->createQueryBus($this->createAdapter([
            [
                ['id' => 1, 'roleId' => 'Admin', 'parentId' => null],
                ['id' => 2, 'roleId' => 'Manager', 'parentId' => '["Admin"]'],
            ],
        ]));

        $attached = null;

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturnCallback(
                static function (ServerRequestInterface $request) use (&$attached): ResponseInterface {
                    $attached = $request->getAttribute(RoleListMiddleware::class);

                    return new HtmlResponse('<main>roles</main>');
                },
            );

        $response = new RoleListMiddleware($bus)->process(new ServerRequest(), $handler);

        self::assertSame('<main>roles</main>', (string) $response->getBody());
        self::assertSame(['Admin' => true], $attached['rolesWithChildren']);
        self::assertCount(2, $attached['roles']);
    }

    #[Test]
    public function returnsTheHandlerResponseUnchanged(): void
    {
        $bus = $this->createQueryBus($this->createAdapter([[]]));

        $expected = new HtmlResponse('<main>roles</main>', 201);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expected);

        self::assertSame($expected, new RoleListMiddleware($bus)->process(new ServerRequest(), $handler));
    }
}

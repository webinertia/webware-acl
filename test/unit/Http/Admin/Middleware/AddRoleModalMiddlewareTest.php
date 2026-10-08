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
use Webware\Acl\Http\Admin\Middleware\AddRoleModalMiddleware;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(AddRoleModalMiddleware::class)]
final class AddRoleModalMiddlewareTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function attachesAllRoles(): void
    {
        $bus = $this->createQueryBus($this->createAdapter([
            [['id' => 1, 'roleId' => 'Admin', 'parentId' => null]],
        ]));

        $attached = null;

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturnCallback(
                static function (ServerRequestInterface $request) use (&$attached): ResponseInterface {
                    $attached = $request->getAttribute(AddRoleModalMiddleware::class);

                    return new HtmlResponse('<div>modal</div>');
                },
            );

        $response = new AddRoleModalMiddleware($bus)->process(new ServerRequest(), $handler);

        self::assertSame('<div>modal</div>', (string) $response->getBody());
        self::assertCount(1, $attached['roles']);
    }

    #[Test]
    public function returnsTheHandlerResponseUnchanged(): void
    {
        $bus = $this->createQueryBus($this->createAdapter([[]]));

        $expected = new HtmlResponse('<div>modal</div>', 201);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expected);

        self::assertSame($expected, new AddRoleModalMiddleware($bus)->process(new ServerRequest(), $handler));
    }
}

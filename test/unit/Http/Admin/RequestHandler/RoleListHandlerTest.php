<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandler;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Http\Admin\RequestHandler\RoleListHandler;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(RoleListHandler::class)]
final class RoleListHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleAddsCloseModalTriggerOnSuccess(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<main>roles</main>');

        $result = $this->createStub(CommandResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Success);

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $name, mixed $default = null): mixed => CommandResult::class === $name
                    ? $result
                    : $default,
            );

        $roleRepo = $this->createQueryBus($this->createAdapter([[]]));
        $response = new RoleListHandler($template, $roleRepo)->handle($request);

        self::assertSame('{"closeModal":null}', $response->getHeaderLine('HX-Trigger'));
    }

    #[Test]
    public function handleComputesRolesWithChildrenAndRenders(): void
    {
        [$name, $params] = [null, null];
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')
            ->willReturnCallback(
                static function (string $template, mixed $model = null) use (&$name, &$params): string {
                    $name   = $template;
                    $params = $model;

                    return '<main>roles</main>';
                },
            );

        $roleRepo = $this->createQueryBus($this->createAdapter([
            [
                ['id' => 1, 'roleId' => 'Admin', 'parentId' => null],
                ['id' => 2, 'roleId' => 'Manager', 'parentId' => '["Admin"]'],
            ],
        ]));

        $response = new RoleListHandler($template, $roleRepo)->handle(
            $this->createStub(ServerRequestInterface::class),
        );

        self::assertSame('<main>roles</main>', (string) $response->getBody());
        self::assertSame('acl::admin-roles', $name);
        self::assertSame(['Admin' => true], $params['rolesWithChildren']);
        self::assertSame('', $response->getHeaderLine('HX-Trigger'));
    }
}

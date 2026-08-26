<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\RequestHandler;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Admin\RequestHandler\EditRoleModalHandler;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(EditRoleModalHandler::class)]
final class EditRoleModalHandlerTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function handleFindsTheRequestedRoleAndRendersModal(): void
    {
        [$name, $params] = [null, null];
        $template = $this->createStub(TemplateRendererInterface::class);
        $template
            ->method('render')
            ->willReturnCallback(
                static function (string $template, mixed $model = null) use (&$name, &$params): string {
                    $name   = $template;
                    $params = $model;

                    return '<div>modal</div>';
                },
            );

        $roleRepo = new RoleRepository($this->createAdapter([
            [
                ['id' => 1, 'roleId' => 'Admin', 'parentId' => null],
                ['id' => 2, 'roleId' => 'Manager', 'parentId' => '["Admin"]'],
            ],
        ]));

        $request = $this->createStub(ServerRequestInterface::class);
        $request
            ->method('getAttribute')
            ->willReturnCallback(
                static fn(string $attribute, mixed $default = null): mixed => 'roleId' === $attribute
                    ? 'Manager'
                    : $default,
            );

        $response = new EditRoleModalHandler($template, $roleRepo)->handle($request);

        self::assertSame('<div>modal</div>', (string) $response->getBody());
        self::assertSame('acl::partials/edit-role-modal', $name);
        self::assertSame('Manager', $params['role']->getRoleId());
        self::assertCount(2, $params['roles']);
    }

    #[Test]
    public function handleLeavesRoleNullWhenNotPresent(): void
    {
        $params   = null;
        $template = $this->createStub(TemplateRendererInterface::class);
        $template
            ->method('render')
            ->willReturnCallback(
                static function (string $template, mixed $model = null) use (&$params): string {
                    $params = $model;

                    return '<div>modal</div>';
                },
            );

        $roleRepo = new RoleRepository($this->createAdapter([
            [['id' => 1, 'roleId' => 'Admin', 'parentId' => null]],
        ]));

        $request = $this->createStub(ServerRequestInterface::class);
        $request
            ->method('getAttribute')
            ->willReturnCallback(
                static fn(string $attribute, mixed $default = null): mixed => 'roleId' === $attribute
                    ? 'Missing'
                    : $default,
            );

        new EditRoleModalHandler($template, $roleRepo)->handle($request);

        self::assertNull($params['role']);
    }
}

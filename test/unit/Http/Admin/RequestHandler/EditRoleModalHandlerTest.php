<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandler;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Entity\Role;
use Webware\Acl\Http\Admin\Middleware\EditRoleModalMiddleware;
use Webware\Acl\Http\Admin\RequestHandler\EditRoleModalHandler;

#[CoversClass(EditRoleModalHandler::class)]
final class EditRoleModalHandlerTest extends TestCase
{
    #[Test]
    public function handleFindsTheRequestedRoleAndRendersModal(): void
    {
        [$name, $params] = [null, null];
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')
            ->willReturnCallback(
                static function (string $template, mixed $model = null) use (&$name, &$params): string {
                    $name   = $template;
                    $params = $model;

                    return '<div>modal</div>';
                },
            );

        $viewModel = [
            'role'  => new Role(
                id      : 2,
                roleId  : 'Manager',
                parentId: '["Admin"]',
            ),
            'roles' => [
                new Role(
                    id    : 1,
                    roleId: 'Admin',
                ),
                new Role(
                    id      : 2,
                    roleId  : 'Manager',
                    parentId: '["Admin"]',
                ),
            ],
        ];

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $attribute, mixed $default = null): mixed => EditRoleModalMiddleware::class
                    === $attribute
                        ? $viewModel
                        : $default,
            );

        $response = new EditRoleModalHandler($template)->handle($request);

        self::assertSame('<div>modal</div>', (string) $response->getBody());
        self::assertSame('acl::partials/edit-role-modal', $name);
        self::assertSame('Manager', $params['role']->getRoleId());
        self::assertCount(2, $params['roles']);
        self::assertFalse($params['layout']);
        self::assertFalse($params['body']);
    }

    #[Test]
    public function handleLeavesRoleNullWhenNotPresent(): void
    {
        $params   = null;
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')
            ->willReturnCallback(
                static function (string $template, mixed $model = null) use (&$params): string {
                    $params = $model;

                    return '<div>modal</div>';
                },
            );

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $attribute, mixed $default = null): mixed => (
                    EditRoleModalMiddleware::class === $attribute
                        ? [
                            'role'  => null,
                            'roles' => [new Role(
                                id    : 1,
                                roleId: 'Admin',
                            )],
                        ]
                        : $default
                ),
            );

        new EditRoleModalHandler($template)->handle($request);

        self::assertNull($params['role']);
    }
}

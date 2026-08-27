<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandlers;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Http\Admin\RequestHandlers\DeleteRuleModalHandler;

#[CoversClass(DeleteRuleModalHandler::class)]
final class DeleteRuleModalHandlerTest extends TestCase
{
    #[Test]
    public function handleRendersModalWithRoleAndResourceIds(): void
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

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $attribute, mixed $default = null): mixed => match ($attribute) {
                    'roleId'     => 'Admin',
                    'resourceId' => 'dashboard',
                    default      => $default,
                },
            );

        $response = new DeleteRuleModalHandler($template)->handle($request);

        self::assertSame('<div>modal</div>', (string) $response->getBody());
        self::assertSame('acl::partials/delete-rule-modal', $name);
        self::assertSame('Admin', $params['roleId']);
        self::assertSame('dashboard', $params['resourceId']);
        self::assertFalse($params['layout']);
        self::assertFalse($params['body']);
    }
}

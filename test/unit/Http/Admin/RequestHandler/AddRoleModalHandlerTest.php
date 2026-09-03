<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandler;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Http\Admin\RequestHandler\AddRoleModalHandler;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(AddRoleModalHandler::class)]
final class AddRoleModalHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleRendersModalWithAllRoles(): void
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

        $roleRepo = $this->createQueryBus($this->createAdapter([
            [['id' => 1, 'roleId' => 'Admin', 'parentId' => null]],
        ]));

        $response = new AddRoleModalHandler($template, $roleRepo)->handle(
            $this->createStub(ServerRequestInterface::class),
        );

        self::assertSame('<div>modal</div>', (string) $response->getBody());
        self::assertSame('acl::partials/add-role-modal', $name);
        self::assertFalse($params['layout']);
        self::assertFalse($params['body']);
        self::assertCount(1, $params['roles']);
    }
}

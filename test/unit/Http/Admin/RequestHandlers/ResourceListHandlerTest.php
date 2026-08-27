<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandlers;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Http\Admin\RequestHandlers\ResourceListHandler;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;

#[CoversClass(ResourceListHandler::class)]
final class ResourceListHandlerTest extends TestCase
{
    #[Test]
    public function handleAddsCloseModalTriggerOnSuccess(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<main>resources</main>');

        $result = $this->createStub(CommandResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Success);

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $name, mixed $default = null): mixed => CommandResult::class === $name
                    ? $result
                    : $default,
            );

        $response = new ResourceListHandler([], $template)->handle($request);

        self::assertSame('{"closeModal":null}', $response->getHeaderLine('HX-Trigger'));
    }

    #[Test]
    public function handleRendersResourcesFromConfig(): void
    {
        [$name, $params] = [null, null];
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')
            ->willReturnCallback(
                static function (string $template, mixed $model = null) use (&$name, &$params): string {
                    $name   = $template;
                    $params = $model;

                    return '<main>resources</main>';
                },
            );

        $handler = new ResourceListHandler(['resources' => [['resourceId' => 'dashboard']]], $template);

        $response = $handler->handle($this->createStub(ServerRequestInterface::class));

        self::assertSame('<main>resources</main>', (string) $response->getBody());
        self::assertSame('acl::admin-resources', $name);
        self::assertSame([['resourceId' => 'dashboard']], $params['resources']);
        self::assertSame('', $response->getHeaderLine('HX-Trigger'));
    }
}

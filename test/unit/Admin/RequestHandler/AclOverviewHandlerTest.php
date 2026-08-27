<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\RequestHandler;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Admin\RequestHandler\AclOverviewHandler;
use Webware\Acl\Http\Admin\Middleware\OverviewMiddleware;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;

#[CoversClass(AclOverviewHandler::class)]
final class AclOverviewHandlerTest extends TestCase
{
    #[Test]
    public function handleAddsCloseModalTriggerOnSuccess(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<main>acl</main>');

        $result = $this->createStub(CommandResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Success);

        $handler  = new AclOverviewHandler($template);
        $response = $handler->handle($this->request([CommandResult::class => $result]));

        self::assertSame('closeModal', $response->getHeaderLine('HX-Trigger'));
    }

    #[Test]
    public function handleRendersViewWithoutTriggerWhenNoCommandResult(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<main>acl</main>');

        $handler  = new AclOverviewHandler($template);
        $response = $handler->handle($this->request([OverviewMiddleware::class => ['foo' => 'bar']]));

        self::assertSame('<main>acl</main>', (string) $response->getBody());
        self::assertSame('', $response->getHeaderLine('HX-Trigger'));
    }

    #[Test]
    public function handleSkipsTriggerOnFailure(): void
    {
        $template = $this->createStub(TemplateRendererInterface::class);
        $template->method('render')->willReturn('<main>acl</main>');

        $result = $this->createStub(CommandResultInterface::class);
        $result->method('getStatus')->willReturn(MessageStatus::Failure);

        $handler  = new AclOverviewHandler($template);
        $response = $handler->handle($this->request([CommandResult::class => $result]));

        self::assertSame('', $response->getHeaderLine('HX-Trigger'));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function request(array $attributes): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->willReturnCallback(
                static fn(string $name, mixed $default = null): mixed => $attributes[$name] ?? $default,
            );

        return $request;
    }
}

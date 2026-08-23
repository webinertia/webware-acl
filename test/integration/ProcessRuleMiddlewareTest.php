<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

#[CoversClass(ProcessRuleMiddleware::class)]
final class ProcessRuleMiddlewareTest extends TestCase
{
    #[Test]
    public function patchWithValidBodyDispatchesUpdateRuleTypeCommandAndSetsSuccess(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(UpdateRuleTypeCommand::class))
            ->willReturnCallback(static fn($cmd) => new CommandResult($cmd, MessageStatus::Success, null));

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = new ServerRequest([], [], '/', 'PATCH')->withAttribute('id', '7')
            ->withParsedBody(['type' => 'deny'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($bus);
        $middleware->processPatch($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
    }

    #[Test]
    public function postWithMissingRolePkSetsFailure(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);

        $request = new ServerRequest([], [], '/', 'POST')->withParsedBody([
            'resource_pk'  => '2',
            'privilege_pk' => '3',
        ]);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function postWithValidBodyDispatchesSaveRuleCommandAndSetsSuccess(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveRuleCommand::class))
            ->willReturnCallback(static fn($cmd) => new CommandResult($cmd, MessageStatus::Success, null));

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = new ServerRequest([], [], '/', 'POST')->withParsedBody([
            'role_pk'      => '1',
            'resource_pk'  => '2',
            'privilege_pk' => '3',
            'type'         => 'allow',
        ])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Blocked on MessageBus query/command refactor of the repository boundary.');
    }

    private function capturingHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public ?ServerRequestInterface $received = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->received = $request;

                return new EmptyResponse();
            }
        };
    }
}

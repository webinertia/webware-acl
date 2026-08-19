<?php

declare(strict_types=1);

namespace Webware\AclIntegrationTest;

use Webware\Message\SystemMessengerInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\MessageBusInterface;

#[CoversClass(ProcessRoleMiddleware::class)]
final class ProcessRoleMiddlewareTest extends TestCase
{
    #[Test]
    public function postWithValidBodyDispatchesSaveRoleCommandAndSetsSuccess(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(fn ($cmd) => $this->successResult($cmd));

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['role_id' => 'Shift Lead', 'parent_pk' => '2'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
    }

    #[Test]
    public function postWithMissingRoleIdSetsFailure(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['parent_pk' => '2']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function postWithZeroParentPkSetsFailure(): void
    {
        $bus = $this->createStub(MessageBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['role_id' => 'Shift Lead', 'parent_pk' => '0']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function patchWithValidBodyDispatchesSaveRoleCommand(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(fn ($cmd) => $this->successResult($cmd));

        $request = (new ServerRequest([], [], '/', 'PATCH'))
            ->withParsedBody(['role_id' => 'Shift Lead', 'parent_pk' => '2']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($bus);
        $middleware->processPatch($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
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

    private function successResult(SaveRoleCommand|DeleteRoleCommand $cmd): CommandResult
    {
        return new CommandResult($cmd, MessageStatus::Success, null);
    }
}

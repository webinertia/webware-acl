<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\InputFilter;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Http\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\InputFilter\RoleDataFilter;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

#[CoversClass(ProcessRoleMiddleware::class)]
final class ProcessRoleMiddlewareTest extends TestCase
{
    #[Test]
    public function processDeleteDispatchesDeleteRoleCommand(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(DeleteRoleCommand::class))
            ->willReturnCallback(
                static fn(DeleteRoleCommand $cmd): CommandResult => new CommandResult(
                    $cmd,
                    MessageStatus::Success,
                    null,
                ),
            );

        $request = $this->request('DELETE', $this->fakeFilter(values: ['roleId' => 'Editor']));
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processDelete($request, $handler);

        self::assertSame(
            MessageStatus::Success,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processDeleteSetsFailureWhenBusFails(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(DeleteRoleCommand::class))
            ->willReturnCallback(
                static fn(DeleteRoleCommand $cmd): CommandResult => new CommandResult(
                    $cmd,
                    MessageStatus::Failure,
                    null,
                ),
            );

        $request = $this->request('DELETE', $this->fakeFilter(values: ['roleId' => 'Editor']));
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processDelete($request, $handler);

        self::assertSame(
            MessageStatus::Failure,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processPatchDispatchesSaveRoleCommandOnValidInput(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(
                static fn(SaveRoleCommand $cmd): CommandResult => new CommandResult($cmd, MessageStatus::Success, null),
            );

        $request = $this->request(
            'PATCH',
            $this->fakeFilter(values: ['id' => 7, 'roleId' => 'Editor', 'parentId' => ['Admin']]),
        );
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processPatch($request, $handler);

        self::assertSame(
            MessageStatus::Success,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processPatchSetsFailureWhenBusFails(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(
                static fn(SaveRoleCommand $cmd): CommandResult => new CommandResult($cmd, MessageStatus::Failure, null),
            );

        $request = $this->request(
            'PATCH',
            $this->fakeFilter(values: ['id' => 7, 'roleId' => 'Editor', 'parentId' => ['Admin']]),
        );
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processPatch($request, $handler);

        self::assertSame(
            MessageStatus::Failure,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processPatchSkipsDispatchOnInvalidInput(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('handle');

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects(self::once())->method('warning');

        $request = $this->request(
            'PATCH',
            $this->fakeFilter(
                valid  : false,
                message: 'Invalid roleId',
            ),
            $messenger,
        );
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processPatch($request, $handler);

        self::assertNull($handler->received?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function processPostDispatchesSaveRoleCommand(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(
                static fn(SaveRoleCommand $cmd): CommandResult => new CommandResult($cmd, MessageStatus::Success, null),
            );

        $request = $this->request(
            'POST',
            $this->fakeFilter(values: ['id' => null, 'roleId' => 'Guest', 'parentId' => null]),
        );
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processPost($request, $handler);

        self::assertSame(
            MessageStatus::Success,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processPostSetsFailureWhenBusFails(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(
                static fn(SaveRoleCommand $cmd): CommandResult => new CommandResult($cmd, MessageStatus::Failure, null),
            );

        $request = $this->request(
            'POST',
            $this->fakeFilter(values: ['id' => null, 'roleId' => 'Guest', 'parentId' => null]),
        );
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processPost($request, $handler);

        self::assertSame(
            MessageStatus::Failure,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processPostSkipsDispatchOnInvalidInput(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('handle');

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects(self::once())->method('warning');

        $request = $this->request(
            'POST',
            $this->fakeFilter(
                valid  : false,
                message: 'Invalid roleId',
            ),
            $messenger,
        );
        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->processPost($request, $handler);

        self::assertNull($handler->received?->getAttribute(CommandResult::class));
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

    private function fakeFilter(bool $valid = true, array $values = [], string $message = ''): InputFilter\InputFilter
    {
        return new class($valid, $values, $message) extends InputFilter\InputFilter {
            public function __construct(
                private bool $valid,
                private array $values,
                private string $message,
            ) {}

            public function getSystemMessage(bool $asJson = false): string
            {
                return $this->message;
            }

            public function getValues(): array
            {
                return $this->values;
            }

            public function isValid(?array $context = null): bool
            {
                return $this->valid;
            }

            public function setData(?iterable $data): static
            {
                return $this;
            }

            public function setValidationGroup(int|string|array $name): static
            {
                return $this;
            }
        };
    }

    private function request(
        string $method,
        InputFilter\InputFilter $filter,
        ?SystemMessengerInterface $messenger = null,
    ): ServerRequest {
        $filterManager = new InputFilter\InputFilterPluginManager(new ServiceManager());
        $filterManager->setService(RoleDataFilter::class, $filter);

        $request = new ServerRequest([], [], '/', $method)->withAttribute(
            InputFilter\InputFilterPluginManager::class,
            $filterManager,
        );
        if (null !== $messenger) {
            $request = $request->withAttribute(SystemMessengerInterface::class, $messenger);
        }

        return $request;
    }
}

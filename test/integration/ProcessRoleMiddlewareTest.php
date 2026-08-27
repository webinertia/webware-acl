<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\InputFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Http\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use WebwareTestIntegration\Acl\Support\FilterManagerFactory;

#[CoversClass(ProcessRoleMiddleware::class)]
final class ProcessRoleMiddlewareTest extends TestCase
{
    private InputFilter\InputFilterPluginManager $filterManager;

    #[Test]
    public function processDeleteDispatchesDeleteRoleCommand(): void
    {
        $captured = null;
        $bus      = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(DeleteRoleCommand::class))
            ->willReturnCallback(static function (DeleteRoleCommand $cmd) use (&$captured): CommandResult {
                $captured = $cmd;

                return new CommandResult($cmd, MessageStatus::Success, null);
            });

        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->process(
            $this->request('DELETE', [], ['roleId' => 'Editor']),
            $handler,
        );

        self::assertInstanceOf(DeleteRoleCommand::class, $captured);
        self::assertSame('Editor', $captured->roleId);
    }

    #[Test]
    public function processPatchDispatchesSaveRoleCommandWithFilteredId(): void
    {
        $captured = null;
        $bus      = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(static function (SaveRoleCommand $cmd) use (&$captured): CommandResult {
                $captured = $cmd;

                return new CommandResult($cmd, MessageStatus::Success, null);
            });

        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->process(
            $this->request('PATCH', ['id' => '7', 'roleId' => 'Editor', 'parentId' => 'Admin']),
            $handler,
        );

        self::assertInstanceOf(SaveRoleCommand::class, $captured);
        self::assertSame(7, $captured->id);
        self::assertSame('Editor', $captured->roleId);
        self::assertSame(['Admin'], $captured->parentId);
    }

    #[Test]
    public function processPostDispatchesSaveRoleCommandWithFilteredValues(): void
    {
        $captured = null;
        $bus      = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveRoleCommand::class))
            ->willReturnCallback(static function (SaveRoleCommand $cmd) use (&$captured): CommandResult {
                $captured = $cmd;

                return new CommandResult($cmd, MessageStatus::Success, null);
            });

        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->process(
            $this->request('POST', ['roleId' => ' Guest ', 'parentId' => 'Admin']),
            $handler,
        );

        self::assertInstanceOf(SaveRoleCommand::class, $captured);
        self::assertNull($captured->id);
        self::assertSame('Guest', $captured->roleId);
        self::assertSame(['Admin'], $captured->parentId);
        self::assertSame(
            MessageStatus::Success,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processPostSkipsDispatchWhenRoleIdMissing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('handle');

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())->method('warning');

        $handler = $this->capturingHandler();

        new ProcessRoleMiddleware($bus)->process(
            $this->request('POST', ['parentId' => 'Admin'], [], $messenger),
            $handler,
        );

        self::assertNull($handler->received?->getAttribute(CommandResult::class));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->filterManager = FilterManagerFactory::create();
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

    /**
     * @param array<array-key, mixed> $body
     * @param array<array-key, mixed> $attributes
     */
    private function request(
        string $method,
        array $body = [],
        array $attributes = [],
        ?SystemMessengerInterface $messenger = null,
    ): ServerRequest {
        $request = new ServerRequest([], [], '/', $method)->withAttribute(
            InputFilter\InputFilterPluginManager::class,
            $this->filterManager,
        );

        if ([] !== $body) {
            $request = $request->withParsedBody($body);
        }

        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute((string) $name, $value);
        }

        if (null !== $messenger) {
            $request = $request->withAttribute(SystemMessengerInterface::class, $messenger);
        }

        return $request;
    }
}

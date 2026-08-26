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
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Acl\RuleType;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;
use WebwareTestIntegration\Acl\Support\FilterManagerFactory;

#[CoversClass(ProcessRuleMiddleware::class)]
final class ProcessRuleMiddlewareTest extends TestCase
{
    private InputFilter\InputFilterPluginManager $filterManager;

    #[Test]
    public function processDeleteDispatchesDeleteRuleCommand(): void
    {
        $captured = null;
        $bus      = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(DeleteRuleCommand::class))
            ->willReturnCallback(static function (DeleteRuleCommand $cmd) use (&$captured): CommandResult {
                $captured = $cmd;

                return new CommandResult($cmd, MessageStatus::Success, null);
            });

        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->process(
            $this->request('DELETE', [], ['roleId' => 'Admin', 'resourceId' => 'dashboard']),
            $handler,
        );

        self::assertInstanceOf(DeleteRuleCommand::class, $captured);
        self::assertSame('Admin', $captured->roleId);
        self::assertSame('dashboard', $captured->resourceId);
    }

    #[Test]
    public function processPatchDispatchesUpdateRuleTypeCommand(): void
    {
        $captured = null;
        $bus      = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(UpdateRuleTypeCommand::class))
            ->willReturnCallback(static function (UpdateRuleTypeCommand $cmd) use (&$captured): CommandResult {
                $captured = $cmd;

                return new CommandResult($cmd, MessageStatus::Success, null);
            });

        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->process(
            $this->request('PATCH', [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'type'       => 'Deny',
            ]),
            $handler,
        );

        self::assertInstanceOf(UpdateRuleTypeCommand::class, $captured);
        self::assertSame('Admin', $captured->roleId);
        self::assertSame('dashboard', $captured->resourceId);
        self::assertSame(RuleType::Deny, $captured->type);
    }

    #[Test]
    public function processPostDispatchesSaveRuleCommandWithFilteredValues(): void
    {
        $captured = null;
        $bus      = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveRuleCommand::class))
            ->willReturnCallback(static function (SaveRuleCommand $cmd) use (&$captured): CommandResult {
                $captured = $cmd;

                return new CommandResult($cmd, MessageStatus::Success, null);
            });

        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->process(
            $this->request('POST', [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'type'       => 'Allow',
                'assertions' => 'Ownership',
            ]),
            $handler,
        );

        self::assertInstanceOf(SaveRuleCommand::class, $captured);
        self::assertSame('Admin', $captured->roleId);
        self::assertSame('dashboard', $captured->resourceId);
        self::assertSame(RuleType::Allow, $captured->type);
        self::assertSame(['Ownership'], $captured->assertions);
    }

    #[Test]
    public function processPostSkipsDispatchWhenTypeMissing(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('handle');

        $messenger = $this->createMock(SystemMessengerInterface::class);
        $messenger->expects($this->once())->method('warning');

        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->process(
            $this->request('POST', ['roleId' => 'Admin', 'resourceId' => 'dashboard'], [], $messenger),
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

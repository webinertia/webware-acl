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
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Http\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Acl\InputFilter\RuleDataFilter;
use Webware\Acl\InputFilter\RuleDeleteFilter;
use Webware\Acl\RuleType;
use Webware\Message\SystemMessengerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

#[CoversClass(ProcessRuleMiddleware::class)]
final class ProcessRuleMiddlewareTest extends TestCase
{
    #[Test]
    public function processDeleteDispatchesDeleteRuleCommand(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(DeleteRuleCommand::class))
            ->willReturnCallback(
                static fn(DeleteRuleCommand $cmd): CommandResult => new CommandResult(
                    $cmd,
                    MessageStatus::Success,
                    null,
                ),
            );

        $request = $this->request(
            'DELETE',
            $this->fakeFilter(values: [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
            ]),
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processDelete($request, $handler);

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
            ->with(self::isInstanceOf(DeleteRuleCommand::class))
            ->willReturnCallback(
                static fn(DeleteRuleCommand $cmd): CommandResult => new CommandResult(
                    $cmd,
                    MessageStatus::Failure,
                    null,
                ),
            );

        $request = $this->request(
            'DELETE',
            $this->fakeFilter(values: [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
            ]),
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processDelete($request, $handler);

        self::assertSame(
            MessageStatus::Failure,
            $handler->received?->getAttribute(CommandResult::class)?->getStatus(),
        );
    }

    #[Test]
    public function processPatchDispatchesUpdateRuleTypeCommand(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(UpdateRuleTypeCommand::class))
            ->willReturnCallback(
                static fn(UpdateRuleTypeCommand $cmd): CommandResult => new CommandResult(
                    $cmd,
                    MessageStatus::Success,
                    null,
                ),
            );

        $request = $this->request(
            'PATCH',
            $this->fakeFilter(values: ['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => RuleType::Deny]),
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processPatch($request, $handler);

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
            ->with(self::isInstanceOf(UpdateRuleTypeCommand::class))
            ->willReturnCallback(
                static fn(UpdateRuleTypeCommand $cmd): CommandResult => new CommandResult(
                    $cmd,
                    MessageStatus::Failure,
                    null,
                ),
            );

        $request = $this->request(
            'PATCH',
            $this->fakeFilter(values: ['roleId' => 'Admin', 'resourceId' => 'dashboard', 'type' => RuleType::Deny]),
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processPatch($request, $handler);

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
                message: 'Invalid rule',
            ),
            $messenger,
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processPatch($request, $handler);

        self::assertNull($handler->received?->getAttribute(CommandResult::class));
    }

    #[Test]
    public function processPostDispatchesSaveRuleCommand(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(SaveRuleCommand::class))
            ->willReturnCallback(
                static fn(SaveRuleCommand $cmd): CommandResult => new CommandResult($cmd, MessageStatus::Success, null),
            );

        $request = $this->request(
            'POST',
            $this->fakeFilter(values: [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'type'       => RuleType::Allow,
                'assertions' => null,
            ]),
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processPost($request, $handler);

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
            ->with(self::isInstanceOf(SaveRuleCommand::class))
            ->willReturnCallback(
                static fn(SaveRuleCommand $cmd): CommandResult => new CommandResult($cmd, MessageStatus::Failure, null),
            );

        $request = $this->request(
            'POST',
            $this->fakeFilter(values: [
                'roleId'     => 'Admin',
                'resourceId' => 'dashboard',
                'type'       => RuleType::Allow,
                'assertions' => null,
            ]),
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processPost($request, $handler);

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
                message: 'Invalid rule',
            ),
            $messenger,
        );
        $handler = $this->capturingHandler();

        new ProcessRuleMiddleware($bus)->processPost($request, $handler);

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

            public function getSystemMessage(InputFilter\ErrorMessages $messages, bool $asJson = false): string
            {
                return $this->message;
            }

            public function validate(iterable $data, array $context = []): InputFilter\InputFilterValidationResult
            {
                if ($this->valid) {
                    $results = [];
                    foreach ($this->values as $name => $value) {
                        $results[$name] = InputFilter\InputValidationResult::pass($name, $value, $value);
                    }

                    return new InputFilter\InputFilterValidationResult($results);
                }

                return new InputFilter\InputFilterValidationResult([
                    'input' => InputFilter\InputValidationResult::fail(
                        'input',
                        null,
                        null,
                        new InputFilter\ErrorMessages(['input' => $this->message]),
                    ),
                ]);
            }
        };
    }

    private function request(
        string $method,
        InputFilter\InputFilter $filter,
        ?SystemMessengerInterface $messenger = null,
    ): ServerRequest {
        $filterManager = new InputFilter\InputFilterPluginManager(new ServiceManager());
        $filterManager->setService(RuleDataFilter::class, $filter);
        $filterManager->setService(RuleDeleteFilter::class, $filter);

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

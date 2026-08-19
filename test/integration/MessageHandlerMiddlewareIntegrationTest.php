<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl;

use Laminas\Permissions\Acl\Role\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\AclInterface;
use Webware\Acl\MessageBus\AuthorizableCommandInterface;
use Webware\Acl\MessageBus\CommandStatus;
use Webware\Acl\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\MessageBus;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;
use RuntimeException;

#[CoversClass(MessageHandlerMiddleware::class)]
final class MessageHandlerMiddlewareIntegrationTest extends TestCase
{
    #[Test]
    public function allowedAuthorizableMessagePassesThroughPipelineToDispatch(): void
    {
        $role    = $this->createStub(RoleInterface::class);
        $message = $this->createStub(AuthorizableCommandInterface::class);
        $message->method('getRole')->willReturn($role);
        $message->method('getPrivilegeId')->willReturn('create');

        $expectedResult = $this->createStub(ResultInterface::class);

        $acl = $this->createMock(AclInterface::class);
        $acl->expects($this->once())
            ->method('isAllowed')
            ->with($role, $message, 'create')
            ->willReturn(true);

        $dispatch = new class($expectedResult) implements MiddlewareInterface {
            public bool $called = false;

            public function __construct(
                private ResultInterface $result,
            ) {}

            public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
            {
                $this->called = true;

                return $this->result;
            }
        };

        $result = $this->buildBus($acl, $dispatch)->handle($message);

        self::assertSame($expectedResult, $result);
        self::assertTrue($dispatch->called);
    }

    #[Test]
    public function deniedAuthorizableMessageReturnsForbiddenWithoutDispatch(): void
    {
        $role    = $this->createStub(RoleInterface::class);
        $message = $this->createStub(AuthorizableCommandInterface::class);
        $message->method('getRole')->willReturn($role);
        $message->method('getPrivilegeId')->willReturn('delete');

        $acl = $this->createMock(AclInterface::class);
        $acl->expects($this->once())
            ->method('isAllowed')
            ->with($role, $message, 'delete')
            ->willReturn(false);

        $dispatch = new class() implements MiddlewareInterface {
            public bool $called = false;

            public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
            {
                $this->called = true;

                throw new RuntimeException('dispatch must not be reached');
            }
        };

        $result = $this->buildBus($acl, $dispatch)->handle($message);

        self::assertSame(CommandStatus::Forbidden, $result->getStatus());
        self::assertSame($message, $result->getCommand());
        self::assertNull($result->getResult());
        self::assertFalse($dispatch->called);
    }

    #[Test]
    public function nonAuthorizableMessagePassesThroughPipelineWithoutAclCheck(): void
    {
        $message = $this->createStub(MessageInterface::class);

        $expectedResult = $this->createStub(ResultInterface::class);

        $acl = $this->createMock(AclInterface::class);
        $acl->expects($this->never())->method('isAllowed');

        $dispatch = new class($expectedResult) implements MiddlewareInterface {
            public bool $called = false;

            public function __construct(
                private ResultInterface $result,
            ) {}

            public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
            {
                $this->called = true;

                return $this->result;
            }
        };

        $result = $this->buildBus($acl, $dispatch)->handle($message);

        self::assertSame($expectedResult, $result);
        self::assertTrue($dispatch->called);
    }

    private function buildBus(AclInterface $acl, MiddlewareInterface $dispatch): MessageBus
    {
        $pipe = new MiddlewarePipe();
        $pipe->pipe(new MessageHandlerMiddleware($acl));
        $pipe->pipe($dispatch);

        return new MessageBus($pipe);
    }
}

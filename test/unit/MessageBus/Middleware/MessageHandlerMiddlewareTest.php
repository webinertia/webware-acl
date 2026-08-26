<?php

declare(strict_types=1);

namespace WebwareTest\Acl\MessageBus\Middleware;

use Laminas\Permissions\Acl\Role\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\MessageBus\AuthorizableCommandInterface;
use Webware\Acl\MessageBus\CommandResult;
use Webware\Acl\MessageBus\CommandStatus;
use Webware\Acl\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\Core\AclInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

#[CoversClass(MessageHandlerMiddleware::class)]
final class MessageHandlerMiddlewareTest extends TestCase
{
    #[Test]
    public function authorizableMessageAllowedByAclPassesToNext(): void
    {
        $role    = $this->createStub(RoleInterface::class);
        $message = $this->createStub(AuthorizableCommandInterface::class);
        $message->method('getRole')->willReturn($role);
        $message->method('getPrivilegeId')->willReturn('create');

        $result = $this->createStub(ResultInterface::class);

        $next = $this->createMock(PipelineHandlerInterface::class);
        $next->expects($this->once())
            ->method('handle')
            ->with($message)
            ->willReturn($result);

        $acl = $this->createMock(AclInterface::class);
        $acl->expects($this->once())
            ->method('isAllowed')
            ->with($role, $message, 'create')
            ->willReturn(true);

        $returned = new MessageHandlerMiddleware($acl)->process($message, $next);

        self::assertSame($result, $returned);
    }

    #[Test]
    public function authorizableMessageDeniedByAclReturnsForbiddenWithoutCallingNext(): void
    {
        $role    = $this->createStub(RoleInterface::class);
        $message = $this->createStub(AuthorizableCommandInterface::class);
        $message->method('getRole')->willReturn($role);
        $message->method('getPrivilegeId')->willReturn('delete');

        $next = $this->createMock(PipelineHandlerInterface::class);
        $next->expects($this->never())->method('handle');

        $acl = $this->createMock(AclInterface::class);
        $acl->expects($this->once())
            ->method('isAllowed')
            ->with($role, $message, 'delete')
            ->willReturn(false);

        $result = new MessageHandlerMiddleware($acl)->process($message, $next);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Forbidden, $result->getStatus());
        self::assertSame($message, $result->getCommand());
        self::assertNull($result->getResult());
    }

    #[Test]
    public function nonAuthorizableMessagePassesThroughWithoutAclCheck(): void
    {
        $message = $this->createStub(MessageInterface::class);
        $result  = $this->createStub(ResultInterface::class);

        $next = $this->createMock(PipelineHandlerInterface::class);
        $next->expects($this->once())
            ->method('handle')
            ->with($message)
            ->willReturn($result);

        $acl = $this->createMock(AclInterface::class);
        $acl->expects($this->never())->method('isAllowed');

        $returned = new MessageHandlerMiddleware($acl)->process($message, $next);

        self::assertSame($result, $returned);
    }
}

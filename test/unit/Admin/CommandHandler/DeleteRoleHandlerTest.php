<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\CommandHandler\DeleteRoleHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(DeleteRoleHandler::class)]
final class DeleteRoleHandlerTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function handleRemovesFromParentsDeletesAndReturnsSuccess(): void
    {
        $handler = new DeleteRoleHandler(new RoleRepository($this->createAdapter([[], []])));
        $result  = $handler->handle(new DeleteRoleCommand('Editor'));

        self::assertSame(MessageStatus::Success, $result->getStatus());
    }

    #[Test]
    public function handleReturnsFailureWhenRepositoryThrows(): void
    {
        $handler = new DeleteRoleHandler(
            new RoleRepository($this->createAdapter([], [], new RuntimeException('boom'))),
        );
        $result = $handler->handle(new DeleteRoleCommand('Editor'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertInstanceOf(RuntimeException::class, $result->getResult());
    }
}

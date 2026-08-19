<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Admin\CommandHandler\SaveRoleHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;

#[CoversClass(SaveRoleHandler::class)]
final class SaveRoleHandlerTest extends TestCase
{
    #[Test]
    public function handleSavesRoleIncrementsVersionAndReturnsSuccessWithPk(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('saveRole')
            ->with('Editor', 1)
            ->willReturn(42);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new SaveRoleCommand('Editor', 1);
        $result  = (new SaveRoleHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame(42, $result->getResult());
    }
}

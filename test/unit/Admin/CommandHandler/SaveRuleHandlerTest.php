<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\CommandHandler\SaveRuleHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageStatus;

#[CoversClass(SaveRuleHandler::class)]
final class SaveRuleHandlerTest extends TestCase
{
    #[Test]
    public function handleSavesRuleIncrementsVersionAndReturnsSuccess(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('saveRule')
            ->with(2, 5, 1, 'allow');
        $repo->expects($this->once())->method('incrementVersion');

        $command = new SaveRuleCommand(2, 5, 1, 'allow');
        $result  = new SaveRuleHandler($repo)->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertNull($result->getResult());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Blocked on MessageBus query/command refactor of the repository boundary.');
    }
}

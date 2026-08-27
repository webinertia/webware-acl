<?php

declare(strict_types=1);

namespace WebwareTest\Acl\MessageBus;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\MessageBus\CommandResult;
use Webware\Acl\MessageBus\CommandStatus;
use Webware\MessageBus\Command\CommandInterface;

#[CoversClass(CommandResult::class)]
final class CommandResultTest extends TestCase
{
    #[Test]
    public function exposesCommandStatusAndResult(): void
    {
        $command = $this->createStub(CommandInterface::class);
        $result  = new CommandResult($command, CommandStatus::Forbidden, ['reason' => 'nope']);

        self::assertSame($command, $result->getCommand());
        self::assertSame(CommandStatus::Forbidden, $result->getStatus());
        self::assertSame(['reason' => 'nope'], $result->getResult());
    }
}

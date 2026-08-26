<?php

declare(strict_types=1);

namespace WebwareTest\Acl\MessageBus;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\MessageBus\CommandStatus;

#[CoversClass(CommandStatus::class)]
final class CommandStatusTest extends TestCase
{
    #[Test]
    public function exposesThreeCases(): void
    {
        self::assertSame(
            [CommandStatus::Success, CommandStatus::Failure, CommandStatus::Forbidden],
            CommandStatus::cases(),
        );
    }
}

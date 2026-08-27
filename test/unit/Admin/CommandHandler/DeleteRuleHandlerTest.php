<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\CommandHandler\DeleteRuleHandler;
use Webware\Acl\Repository\RuleRepository;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(DeleteRuleHandler::class)]
final class DeleteRuleHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleDeletesAndReturnsSuccess(): void
    {
        $handler = new DeleteRuleHandler(new RuleRepository($this->createAdapter([[]])));
        $result  = $handler->handle(new DeleteRuleCommand('Admin', 'dashboard'));

        self::assertSame(MessageStatus::Success, $result->getStatus());
    }

    #[Test]
    public function handleReturnsFailureWhenNothingDeleted(): void
    {
        $handler = new DeleteRuleHandler(new RuleRepository($this->createAdapter([[]], [0])));
        $result  = $handler->handle(new DeleteRuleCommand('Admin', 'dashboard'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function handleReturnsFailureWhenRepositoryThrows(): void
    {
        $handler = new DeleteRuleHandler(
            new RuleRepository($this->createAdapter([], [], new RuntimeException('boom'))),
        );
        $result = $handler->handle(new DeleteRuleCommand('Admin', 'dashboard'));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertInstanceOf(RuntimeException::class, $result->getResult());
    }
}

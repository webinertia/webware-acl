<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\CommandHandler\SaveRuleHandler;
use Webware\Acl\RuleType;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(SaveRuleHandler::class)]
final class SaveRuleHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleReturnsFailureWhenRepositoryThrows(): void
    {
        $handler = new SaveRuleHandler(
            $this->createRuleRepository($this->createAdapter([], [], new RuntimeException('boom'))),
            $this->createRoleRepository($this->createAdapter([])),
        );
        $result = $handler->handle(new SaveRuleCommand('Admin', 'dashboard', RuleType::Allow, null));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
        self::assertInstanceOf(RuntimeException::class, $result->getResult());
    }

    #[Test]
    public function handleReturnsFailureWhenSaveReturnsFalse(): void
    {
        $handler = new SaveRuleHandler(
            $this->createRuleRepository($this->createAdapter([[], []], [], null, null)),
            $this->createRoleRepository($this->createAdapter([])),
        );
        $result = $handler->handle(new SaveRuleCommand('Admin', 'dashboard', RuleType::Allow, null));

        self::assertSame(MessageStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function handleSavesRuleAndReturnsSuccess(): void
    {
        $handler = new SaveRuleHandler(
            $this->createRuleRepository($this->createAdapter([[], []])),
            $this->createRoleRepository($this->createAdapter([])),
        );
        $result = $handler->handle(new SaveRuleCommand('Admin', 'dashboard', RuleType::Allow, null));

        self::assertSame(MessageStatus::Success, $result->getStatus());
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\RuleType;

#[CoversClass(UpdateRuleTypeCommand::class)]
final class UpdateRuleTypeCommandTest extends TestCase
{
    #[Test]
    public function exposesNotificationMessages(): void
    {
        $command = new UpdateRuleTypeCommand('Admin', 'dashboard', RuleType::Deny);

        self::assertSame('Rule updated.', $command->successMessage);
        self::assertSame('Rule update failed. Please try again.', $command->failureMessage);
    }

    #[Test]
    public function exposesRuleTypeData(): void
    {
        $command = new UpdateRuleTypeCommand('Admin', 'dashboard', RuleType::Deny);

        self::assertSame('Admin', $command->roleId);
        self::assertSame('dashboard', $command->resourceId);
        self::assertSame(RuleType::Deny, $command->type);
    }
}

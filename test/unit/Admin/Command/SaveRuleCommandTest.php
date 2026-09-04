<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\RuleType;

#[CoversClass(SaveRuleCommand::class)]
final class SaveRuleCommandTest extends TestCase
{
    #[Test]
    public function assertionsDefaultToNull(): void
    {
        $command = new SaveRuleCommand('Admin', 'dashboard', RuleType::Allow);

        self::assertNull($command->assertions);
    }

    #[Test]
    public function exposesNotificationMessages(): void
    {
        $command = new SaveRuleCommand('Admin', 'dashboard', RuleType::Allow);

        self::assertSame('Rule saved.', $command->successMessage);
        self::assertSame('Rule could not be saved. Please try again.', $command->failureMessage);
    }

    #[Test]
    public function exposesRuleData(): void
    {
        $command = new SaveRuleCommand('Admin', 'dashboard', RuleType::Deny, ['Ownership']);

        self::assertSame('Admin', $command->roleId);
        self::assertSame('dashboard', $command->resourceId);
        self::assertSame(RuleType::Deny, $command->type);
        self::assertSame(['Ownership'], $command->assertions);
    }
}

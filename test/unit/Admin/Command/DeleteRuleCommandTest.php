<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\DeleteRuleCommand;

#[CoversClass(DeleteRuleCommand::class)]
final class DeleteRuleCommandTest extends TestCase
{
    #[Test]
    public function exposesRoleAndResourceIds(): void
    {
        $command = new DeleteRuleCommand('Admin', 'dashboard');

        self::assertSame('Admin', $command->roleId);
        self::assertSame('dashboard', $command->resourceId);
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\DeleteRoleCommand;

#[CoversClass(DeleteRoleCommand::class)]
final class DeleteRoleCommandTest extends TestCase
{
    #[Test]
    public function exposesNotificationMessages(): void
    {
        $command = new DeleteRoleCommand('Editor');

        self::assertSame('Role deleted.', $command->successMessage);
        self::assertSame('Role could not be deleted. Please try again.', $command->failureMessage);
    }

    #[Test]
    public function exposesRoleId(): void
    {
        self::assertSame('Editor', new DeleteRoleCommand('Editor')->roleId);
    }
}

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
    public function exposesRoleId(): void
    {
        self::assertSame('Editor', new DeleteRoleCommand('Editor')->roleId);
    }
}

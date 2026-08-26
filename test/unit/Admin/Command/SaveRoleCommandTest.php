<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\SaveRoleCommand;

#[CoversClass(SaveRoleCommand::class)]
final class SaveRoleCommandTest extends TestCase
{
    #[Test]
    public function exposesRoleData(): void
    {
        $command = new SaveRoleCommand(7, 'Editor', ['Admin']);

        self::assertSame(7, $command->id);
        self::assertSame('Editor', $command->roleId);
        self::assertSame(['Admin'], $command->parentId);
    }

    #[Test]
    public function idAndParentIdDefaultToNull(): void
    {
        $command = new SaveRoleCommand(null, 'Guest');

        self::assertNull($command->id);
        self::assertSame('Guest', $command->roleId);
        self::assertNull($command->parentId);
    }
}

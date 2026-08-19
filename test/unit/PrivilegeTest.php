<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\PrivilegeInterface;

#[CoversClass(PrivilegeInterface::class)]
final class PrivilegeTest extends TestCase
{
    #[Test]
    public function createConstantValue(): void
    {
        self::assertSame('create', PrivilegeInterface::CREATE);
    }

    #[Test]
    public function deleteConstantValue(): void
    {
        self::assertSame('delete', PrivilegeInterface::DELETE);
    }

    #[Test]
    public function readConstantValue(): void
    {
        self::assertSame('read', PrivilegeInterface::READ);
    }

    #[Test]
    public function updateConstantValue(): void
    {
        self::assertSame('update', PrivilegeInterface::UPDATE);
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\PrivilegeInterface;

#[CoversNothing]
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
    public function methodPrivilegeMapMapsHttpMethodsToPrivileges(): void
    {
        self::assertSame(
            [
                'GET'    => 'read',
                'HEAD'   => 'read',
                'POST'   => 'create',
                'PUT'    => 'update',
                'PATCH'  => 'update',
                'DELETE' => 'delete',
            ],
            PrivilegeInterface::METHOD_PRIVILEGE_MAP,
        );
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

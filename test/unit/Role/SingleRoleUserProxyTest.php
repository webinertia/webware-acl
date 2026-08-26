<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Role;

use Exception;
use Laminas\Permissions\Acl\Role\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Role\SingleRoleUserProxy;
use Webware\Core\UserInterface;

#[CoversClass(SingleRoleUserProxy::class)]
final class SingleRoleUserProxyTest extends TestCase
{
    #[Test]
    public function delegatesDetailAndIdentityMethodsToUser(): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getIdentity')->willReturn('joe');
        $user->method('getDetails')->willReturn(['role' => 'Admin']);
        $user->method('getDetail')->willReturn('Admin');
        $user->method('getOwnerId')->willReturn(7);
        $user->method('getResourceId')->willReturn('dashboard');

        $proxy = new SingleRoleUserProxy($user, 'Admin');

        self::assertSame('joe', $proxy->getIdentity());
        self::assertSame(['role' => 'Admin'], $proxy->getDetails());
        self::assertSame('Admin', $proxy->getDetail('role'));
        self::assertSame(7, $proxy->getOwnerId());
        self::assertSame('dashboard', $proxy->getResourceId());
    }

    #[Test]
    public function idDefaultsToNull(): void
    {
        $proxy = new SingleRoleUserProxy($this->createStub(UserInterface::class), 'Admin');

        self::assertNull($proxy->id);
    }

    #[Test]
    public function populateThrows(): void
    {
        $proxy = new SingleRoleUserProxy($this->createStub(UserInterface::class), 'Admin');

        $this->expectException(Exception::class);

        $proxy->populate([]);
    }

    #[Test]
    public function resolvesRoleInterfaceRoleId(): void
    {
        $role = $this->createStub(RoleInterface::class);
        $role->method('getRoleId')->willReturn('Manager');

        $proxy = new SingleRoleUserProxy($this->createStub(UserInterface::class), $role);

        self::assertSame('Manager', $proxy->getRoleId());
        self::assertSame(['Manager'], $proxy->getRoles());
    }

    #[Test]
    public function resolvesStringRoleId(): void
    {
        $proxy = new SingleRoleUserProxy($this->createStub(UserInterface::class), 'Admin');

        self::assertSame('Admin', $proxy->getRoleId());
        self::assertSame(['Admin'], $proxy->getRoles());
    }

    #[Test]
    public function toArrayThrows(): void
    {
        $proxy = new SingleRoleUserProxy($this->createStub(UserInterface::class), 'Admin');

        $this->expectException(Exception::class);

        $proxy->toArray();
    }

    #[Test]
    public function withIdThrows(): void
    {
        $proxy = new SingleRoleUserProxy($this->createStub(UserInterface::class), 'Admin');

        $this->expectException(Exception::class);

        $proxy->withId(1);
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Role;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Role\SingleRoleUserProxy;
use Webware\Acl\Role\UserRoleIterator;
use Webware\UserManager\UserInterface;

#[CoversClass(UserRoleIterator::class)]
final class UserRoleIteratorTest extends TestCase
{
    #[Test]
    public function iteratesOverUserRoles(): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn(['Admin', 'Guest']);

        $roles = [];
        foreach (new UserRoleIterator($user) as $proxy) {
            $roles[] = $proxy->getRoleId();
        }

        self::assertSame(['Admin', 'Guest'], $roles);
    }

    #[Test]
    public function supportsManualNavigation(): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn(['Admin']);

        $iterator = new UserRoleIterator($user);

        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->key());
        self::assertInstanceOf(SingleRoleUserProxy::class, $iterator->current());
        self::assertSame('Admin', $iterator->current()->getRoleId());

        $iterator->next();
        self::assertFalse($iterator->valid());

        $iterator->rewind();
        self::assertTrue($iterator->valid());
        self::assertSame(0, $iterator->key());
    }
}

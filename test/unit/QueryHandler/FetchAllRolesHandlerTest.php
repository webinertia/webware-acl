<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Query\FetchAllRoles;
use Webware\Acl\QueryHandler\FetchAllRolesHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchAllRolesHandler::class)]
final class FetchAllRolesHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleReturnsAllRoles(): void
    {
        $handler = new FetchAllRolesHandler(new RoleRepository($this->createAdapter([
            [['id' => 1, 'roleId' => 'Admin', 'parentId' => null]],
        ])));
        $query  = new FetchAllRoles();
        $result = $handler->handle($query);

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($query, $result->getQuery());

        $roles = $result->getResult();
        self::assertCount(1, $roles);
        self::assertSame('Admin', $roles[0]->getRoleId());
    }
}

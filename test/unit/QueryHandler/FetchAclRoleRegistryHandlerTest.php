<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler;

use Laminas\Permissions\Acl\Role\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Query\FetchAclRoleRegistry;
use Webware\Acl\QueryHandler\FetchAclRoleRegistryHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\MessageBus\MessageStatus;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchAclRoleRegistryHandler::class)]
final class FetchAclRoleRegistryHandlerTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function handleReturnsRoleRegistry(): void
    {
        $handler = new FetchAclRoleRegistryHandler(new RoleRepository($this->createAdapter([
            [['id' => 1, 'roleId' => 'Admin', 'parentId' => null]],
        ])));
        $query  = new FetchAclRoleRegistry();
        $result = $handler->handle($query);

        self::assertSame(MessageStatus::Success, $result->getStatus());
        self::assertSame($query, $result->getQuery());
        self::assertInstanceOf(Registry::class, $result->getResult());
    }
}

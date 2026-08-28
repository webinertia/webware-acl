<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\QueryHandler\Container\FetchAllRolesHandlerFactory;
use Webware\Acl\QueryHandler\FetchAllRolesHandler;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchAllRolesHandlerFactory::class)]
final class FetchAllRolesHandlerFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [RoleRepository::class, new RoleRepository($this->createAdapter([]))],
            ]);

        self::assertInstanceOf(FetchAllRolesHandler::class, (new FetchAllRolesHandlerFactory())($container));
    }
}

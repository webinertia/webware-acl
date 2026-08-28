<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\QueryHandler\Container\FetchAclRoleRegistryHandlerFactory;
use Webware\Acl\QueryHandler\FetchAclRoleRegistryHandler;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchAclRoleRegistryHandlerFactory::class)]
final class FetchAclRoleRegistryHandlerFactoryTest extends TestCase
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

        self::assertInstanceOf(
            FetchAclRoleRegistryHandler::class,
            (new FetchAclRoleRegistryHandlerFactory())($container),
        );
    }
}

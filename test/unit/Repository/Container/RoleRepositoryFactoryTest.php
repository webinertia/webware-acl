<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Repository\Container;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Repository\Container\RoleRepositoryFactory;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(RoleRepositoryFactory::class)]
final class RoleRepositoryFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsRepository(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [AdapterInterface::class, $this->createAdapter([])],
            ]);

        self::assertInstanceOf(RoleRepository::class, (new RoleRepositoryFactory())($container));
    }
}

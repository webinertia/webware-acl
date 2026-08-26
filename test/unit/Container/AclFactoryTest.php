<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Container;

use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Acl;
use Webware\Acl\Assertion\AssertionAggregateFactory;
use Webware\Acl\AssertionManager;
use Webware\Acl\Container\AclFactory;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\RuleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(AclFactory::class)]
final class AclFactoryTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function invokeBuildsAclFromContainerServices(): void
    {
        $adapter   = $this->createAdapter([]);
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [RoleRepository::class, new RoleRepository($adapter)],
                [RuleRepository::class, new RuleRepository($adapter)],
                [
                    AssertionAggregateFactory::class,
                    new AssertionAggregateFactory(new AssertionManager(new ServiceManager())),
                ],
                [RouteCollectorInterface::class, $this->createStub(RouteCollectorInterface::class)],
            ]);

        self::assertInstanceOf(Acl::class, (new AclFactory())($container));
    }
}

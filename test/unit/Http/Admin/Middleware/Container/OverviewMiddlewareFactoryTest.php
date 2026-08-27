<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\Middleware\Container;

use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\AssertionManager;
use Webware\Acl\Http\Admin\Middleware\Container\OverviewMiddlewareFactory;
use Webware\Acl\Http\Admin\Middleware\OverviewMiddleware;
use Webware\Acl\Repository\RuleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(OverviewMiddlewareFactory::class)]
final class OverviewMiddlewareFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [RuleRepository::class, new RuleRepository($this->createAdapter([]))],
                [RouteCollectorInterface::class, $this->createStub(RouteCollectorInterface::class)],
                [AssertionManager::class, new AssertionManager(new ServiceManager())],
            ]);

        self::assertInstanceOf(OverviewMiddleware::class, (new OverviewMiddlewareFactory())($container));
    }
}

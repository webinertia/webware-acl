<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Assertion;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Assertion\AssertionAggregateFactory;
use Webware\Acl\Assertion\AssertionAggregateFactoryFactory;
use Webware\Acl\AssertionManager;

#[CoversClass(AssertionAggregateFactoryFactory::class)]
final class AssertionAggregateFactoryFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsFactory(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [AssertionManager::class, new AssertionManager(new ServiceManager())],
            ]);

        self::assertInstanceOf(AssertionAggregateFactory::class, (new AssertionAggregateFactoryFactory())($container));
    }
}

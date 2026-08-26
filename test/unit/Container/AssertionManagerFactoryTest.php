<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\AssertionManager;
use Webware\Acl\Container\AssertionManagerFactory;

#[CoversClass(AssertionManagerFactory::class)]
final class AssertionManagerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsAssertionManagerFromConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        AssertionManager::class => [
                            'aliases'   => ['Ownership' => OwnershipAssertion::class],
                            'factories' => [OwnershipAssertion::class => OwnershipAssertion::class],
                        ],
                    ],
                ],
            ]);

        self::assertInstanceOf(AssertionManager::class, (new AssertionManagerFactory())($container));
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Validator\Container;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\AssertionManager;
use Webware\Acl\Validator\Assertion;
use Webware\Acl\Validator\Container\AssertionFactory;

#[CoversClass(AssertionFactory::class)]
final class AssertionFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsAssertion(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [AssertionManager::class, new AssertionManager(new ServiceManager())],
            ]);

        self::assertInstanceOf(
            Assertion::class,
            (new AssertionFactory())($container, Assertion::class, ['nullable' => true]),
        );
    }
}

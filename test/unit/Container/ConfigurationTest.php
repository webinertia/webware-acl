<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\AssertionManager;
use Webware\Acl\Container\Configuration;
use Webware\Core\AclInterface;
use Webware\Core\Exception\ContainerException;

#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    #[Test]
    public function exposesConfigConstants(): void
    {
        self::assertSame(AclInterface::class, Configuration::CONFIG_KEY);
        self::assertSame('acl.manager', Configuration::ADMIN_ROUTE_SEGMENT_VALUE);
        self::assertSame('acl.manager.', Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE);
    }

    #[Test]
    public function returnsAssertionManagerConfigWhenPresent(): void
    {
        $container = $this->containerWith([AssertionManager::class => ['aliases' => []]]);

        self::assertSame(
            ['aliases' => []],
            Configuration::getAssertionManagerConfig($container, 'TestFactory'),
        );
    }

    #[Test]
    public function throwsWhenAssertionManagerConfigIsEmpty(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getAssertionManagerConfig(
            $this->containerWith([AssertionManager::class => []]),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenAssertionManagerConfigIsNotAnArray(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getAssertionManagerConfig(
            $this->containerWith([AssertionManager::class => 'nope']),
            'TestFactory',
        );
    }

    #[Test]
    public function throwsWhenAssertionManagerKeyMissing(): void
    {
        $this->expectException(ContainerException::class);

        Configuration::getAssertionManagerConfig($this->containerWith([]), 'TestFactory');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function containerWith(array $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap([['config', $config]]);

        return $container;
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Webware\Acl\Console\Container\RuleSeedCollectorFactory;
use Webware\Acl\Console\Seed\RuleSeedCollector;
use Webware\Admin\AdminInterface;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Acl\RuleType;
use Webware\Core\AclInterface;
use Webware\Core\Exception\ConfigurationException;

#[CoversClass(RuleSeedCollectorFactory::class)]
#[CoversMethod(RuleSeedCollectorFactory::class, '__invoke')]
final class RuleSeedCollectorFactoryTest extends TestCase
{
    #[Test]
    public function invokeCollectsNothingWhenNoProviderIsPublished(): void
    {
        $collector = new RuleSeedCollectorFactory()($this->container(providers: null));

        self::assertSame([], $collector->collect());
    }

    #[Test]
    public function invokeRejectsAPublishedServiceThatIsNotAProvider(): void
    {
        $this->expectException(ConfigurationException::class);

        new RuleSeedCollectorFactory()($this->container(
            providers: ['not.a.provider'],
            services: ['not.a.provider' => new stdClass()],
        ));
    }

    #[Test]
    public function invokeResolvesEveryPublishedProviderAndPassesTheAdminName(): void
    {
        $seed = new RuleSeed(
            type      : RuleType::Allow,
            roleId    : 'Guest',
            resourceId: 'a',
        );
        $provider = $this->createMock(RuleSeedProviderInterface::class);
        $provider->expects($this->once())->method('ruleSeeds')->with('admin')->willReturn([$seed]);

        $collector = new RuleSeedCollectorFactory()($this->container(
            providers: ['provider.one'],
            services: ['provider.one' => $provider],
        ));

        self::assertInstanceOf(RuleSeedCollector::class, $collector);
        self::assertSame([$seed], $collector->collect());
    }

    /**
     * @param list<string>|null     $providers
     * @param array<string, object> $services
     */
    private function container(?array $providers, array $services = []): ContainerInterface
    {
        $config = [AdminInterface::class => []];

        if (null !== $providers) {
            $config[AclInterface::class] = ['rule_seed_providers' => $providers];
        }

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => 'config' === $id ? $config : $services[$id],
            );

        return $container;
    }
}

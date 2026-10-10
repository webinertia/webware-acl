<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Container;

use Mezzio\Router\RouteCollectorInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Console\Container\SeedCommandFactory;
use Webware\Acl\Console\Container\SeedRunnerFactory;
use Webware\Acl\Console\Seed\RuleSeedCollector;
use Webware\Acl\Console\Seed\RuleSeeder;
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Acl\Console\Seed\SeedRunner;
use Webware\Acl\Console\SeedCommand;
use WebwareTest\Acl\Support\SeedRunnerTrait;

#[CoversClass(SeedRunnerFactory::class)]
#[CoversClass(SeedCommandFactory::class)]
#[CoversMethod(SeedRunnerFactory::class, '__invoke')]
#[CoversMethod(SeedCommandFactory::class, '__invoke')]
final class SeedFactoriesTest extends TestCase
{
    use SeedRunnerTrait;

    #[Test]
    public function seedCommandFactoryWrapsTheRunner(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap([[SeedRunner::class, $this->seedRunner()]]);

        self::assertInstanceOf(SeedCommand::class, new SeedCommandFactory()($container));
    }

    #[Test]
    public function seedRunnerFactoryComposesCollectorSeederAndRoutes(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [RuleSeedCollector::class, new RuleSeedCollector([], 'admin')],
                [
                    RuleSeeder::class,
                    new RuleSeeder(
                        adapter  : $this->createStub(AdapterInterface::class),
                        table    : new TableIdentifier(table: 'acl_rule'),
                        metadata : $this->createStub(MetadataInterface::class),
                        validator: new RuleSeedValidator(),
                    ),
                ],
                [RouteCollectorInterface::class, $this->createStub(RouteCollectorInterface::class)],
            ]);

        self::assertInstanceOf(SeedRunner::class, new SeedRunnerFactory()($container));
    }
}

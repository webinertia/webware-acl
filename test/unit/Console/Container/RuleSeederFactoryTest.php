<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\SchemaFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Console\Container\RuleSeederFactory;
use Webware\Acl\Console\Seed\RuleSeeder;

#[CoversClass(RuleSeederFactory::class)]
#[CoversMethod(RuleSeederFactory::class, '__invoke')]
final class RuleSeederFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsTheSeederOverTheResolvedRuleTable(): void
    {
        $metadata = $this->createStub(MetadataInterface::class);
        $metadata->method('getTableNames')->willReturn(['acl_rule']);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [AdapterInterface::class, $this->createStub(AdapterInterface::class)],
                [SchemaFactory::class, new SchemaFactory()],
                [MetadataInterface::class, $metadata],
            ]);

        $seeder = new RuleSeederFactory()($container);

        self::assertInstanceOf(RuleSeeder::class, $seeder);
        self::assertTrue($seeder->ruleTableExists());
    }
}

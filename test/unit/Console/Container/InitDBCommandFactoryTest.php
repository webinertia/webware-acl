<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Console\Container\InitDBCommandFactory;
use Webware\Acl\Console\InitDBCommand;
use Webware\Acl\Console\Seed\SeedRunner;
use WebwareTest\Acl\Support\SeedRunnerTrait;

#[CoversClass(InitDBCommandFactory::class)]
final class InitDBCommandFactoryTest extends TestCase
{
    use SeedRunnerTrait;

    #[Test]
    public function invokeBuildsCommandWithAdapter(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [AdapterInterface::class, $this->createStub(AdapterInterface::class)],
                [SeedRunner::class, $this->seedRunner()],
            ]);

        self::assertInstanceOf(InitDBCommand::class, new InitDBCommandFactory()($container));
    }
}

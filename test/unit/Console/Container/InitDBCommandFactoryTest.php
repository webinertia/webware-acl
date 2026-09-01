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

#[CoversClass(InitDBCommandFactory::class)]
final class InitDBCommandFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsCommandWithAdapter(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [AdapterInterface::class, $this->createStub(AdapterInterface::class)],
            ]);

        self::assertInstanceOf(InitDBCommand::class, (new InitDBCommandFactory())($container));
    }
}

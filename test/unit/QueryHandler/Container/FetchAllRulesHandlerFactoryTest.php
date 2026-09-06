<?php

declare(strict_types=1);

namespace WebwareTest\Acl\QueryHandler\Container;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\QueryHandler\Container\FetchAllRulesHandlerFactory;
use Webware\Acl\QueryHandler\FetchAllRulesHandler;
use Webware\Core\SchemaFactory;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(FetchAllRulesHandlerFactory::class)]
final class FetchAllRulesHandlerFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [SchemaFactory::class, new SchemaFactory([])],
                [AdapterInterface::class, $this->createAdapter([])],
            ]);

        self::assertInstanceOf(FetchAllRulesHandler::class, (new FetchAllRulesHandlerFactory())($container));
    }
}

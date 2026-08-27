<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\Middleware\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\Admin\Middleware\Container\ProcessRuleMiddlewareFactory;
use Webware\Acl\Http\Admin\Middleware\ProcessRuleMiddleware;
use Webware\MessageBus\MessageBusInterface;

#[CoversClass(ProcessRuleMiddlewareFactory::class)]
final class ProcessRuleMiddlewareFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsMiddleware(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [MessageBusInterface::class, $this->createStub(MessageBusInterface::class)],
            ]);

        self::assertInstanceOf(ProcessRuleMiddleware::class, (new ProcessRuleMiddlewareFactory())($container));
    }
}

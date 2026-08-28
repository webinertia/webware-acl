<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandlers\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\Admin\RequestHandlers\Container\RoleListHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandlers\RoleListHandler;
use Webware\MessageBus\MessageBusInterface;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(RoleListHandlerFactory::class)]
final class RoleListHandlerFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [MessageBusInterface::class, $this->createQueryBus($this->createAdapter([]))],
            ]);

        self::assertInstanceOf(RoleListHandler::class, (new RoleListHandlerFactory())($container));
    }
}

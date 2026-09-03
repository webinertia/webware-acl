<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\Admin\RequestHandler\AddRoleModalHandler;
use Webware\Acl\Http\Admin\RequestHandler\Container\AddRoleModalHandlerFactory;
use Webware\MessageBus\MessageBusInterface;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(AddRoleModalHandlerFactory::class)]
final class AddRoleModalHandlerFactoryTest extends TestCase
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

        self::assertInstanceOf(AddRoleModalHandler::class, (new AddRoleModalHandlerFactory())($container));
    }
}

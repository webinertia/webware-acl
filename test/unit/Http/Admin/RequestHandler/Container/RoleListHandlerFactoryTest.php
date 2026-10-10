<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\Admin\RequestHandler\Container\RoleListHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandler\RoleListHandler;

#[CoversClass(RoleListHandlerFactory::class)]
final class RoleListHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        self::assertInstanceOf(RoleListHandler::class, new RoleListHandlerFactory()($container));
    }
}

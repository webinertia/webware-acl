<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandlers\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\Admin\RequestHandlers\AclOverviewHandler;
use Webware\Acl\Http\Admin\RequestHandlers\Container\AclOverviewHandlerFactory;

#[CoversClass(AclOverviewHandlerFactory::class)]
final class AclOverviewHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        self::assertInstanceOf(AclOverviewHandler::class, (new AclOverviewHandlerFactory())($container));
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandlers\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Http\Admin\RequestHandlers\Container\ResourceListHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandlers\ResourceListHandler;
use Webware\Core\AclInterface;

#[CoversClass(ResourceListHandlerFactory::class)]
final class ResourceListHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandlerFromConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['config', [AclInterface::class => ['resources' => []]]],
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        self::assertInstanceOf(ResourceListHandler::class, (new ResourceListHandlerFactory())($container));
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Admin\RequestHandler\Container\ResourceListHandlerFactory;
use Webware\Acl\Admin\RequestHandler\ResourceListHandler;

#[CoversClass(ResourceListHandlerFactory::class)]
final class ResourceListHandlerFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsHandlerFromConfig(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                ['config', [AclInterface::class => ['resources' => []]]],
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        self::assertInstanceOf(ResourceListHandler::class, (new ResourceListHandlerFactory())($container));
    }
}

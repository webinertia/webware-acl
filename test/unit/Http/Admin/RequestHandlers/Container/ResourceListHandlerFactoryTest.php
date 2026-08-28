<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\RequestHandlers\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
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
                ['config', [AclInterface::class => ['resources' => ['dashboard']]]],
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
            ]);

        $handler = (new ResourceListHandlerFactory())($container);

        self::assertInstanceOf(ResourceListHandler::class, $handler);
        self::assertSame(
            ['resources' => ['dashboard']],
            new ReflectionProperty(ResourceListHandler::class, 'config')->getValue($handler),
        );
    }
}

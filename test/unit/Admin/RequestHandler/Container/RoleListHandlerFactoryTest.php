<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\RequestHandler\Container\RoleListHandlerFactory;
use Webware\Acl\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(RoleListHandlerFactory::class)]
final class RoleListHandlerFactoryTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createStub(TemplateRendererInterface::class)],
                [RoleRepository::class, new RoleRepository($this->createAdapter([]))],
            ]);

        self::assertInstanceOf(RoleListHandler::class, (new RoleListHandlerFactory())($container));
    }
}

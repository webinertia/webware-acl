<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\CommandHandler\Container\DeleteRoleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\DeleteRoleHandler;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(DeleteRoleHandlerFactory::class)]
final class DeleteRoleHandlerFactoryTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [RoleRepository::class, new RoleRepository($this->createAdapter([]))],
            ]);

        self::assertInstanceOf(DeleteRoleHandler::class, (new DeleteRoleHandlerFactory())($container));
    }
}

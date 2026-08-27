<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\CommandHandler\Container\SaveRoleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\SaveRoleHandler;
use Webware\Acl\Repository\RoleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

#[CoversClass(SaveRoleHandlerFactory::class)]
final class SaveRoleHandlerFactoryTest extends TestCase
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

        self::assertInstanceOf(SaveRoleHandler::class, (new SaveRoleHandlerFactory())($container));
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\CommandHandler\Container\UpdateRuleTypeHandlerFactory;
use Webware\Acl\Admin\CommandHandler\UpdateRuleTypeHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\RuleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(UpdateRuleTypeHandlerFactory::class)]
final class UpdateRuleTypeHandlerFactoryTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $adapter   = $this->createAdapter([]);
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [RuleRepository::class, new RuleRepository($adapter)],
                [RoleRepository::class, new RoleRepository($adapter)],
            ]);

        self::assertInstanceOf(UpdateRuleTypeHandler::class, (new UpdateRuleTypeHandlerFactory())($container));
    }
}

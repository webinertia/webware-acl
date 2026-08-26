<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Admin\CommandHandler\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\CommandHandler\Container\DeleteRuleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\DeleteRuleHandler;
use Webware\Acl\Repository\RuleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(DeleteRuleHandlerFactory::class)]
final class DeleteRuleHandlerFactoryTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function invokeBuildsHandler(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [RuleRepository::class, new RuleRepository($this->createAdapter([]))],
            ]);

        self::assertInstanceOf(DeleteRuleHandler::class, (new DeleteRuleHandlerFactory())($container));
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Repository\Container;

use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\Repository\Container\RuleRepositoryFactory;
use Webware\Acl\Repository\RuleRepository;
use WebwareTest\Acl\Support\PhpDbAdapterMock;

#[CoversClass(RuleRepositoryFactory::class)]
final class RuleRepositoryFactoryTest extends TestCase
{
    use PhpDbAdapterMock;

    #[Test]
    public function invokeBuildsRepository(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                [AdapterInterface::class, $this->createAdapter([])],
            ]);

        self::assertInstanceOf(RuleRepository::class, (new RuleRepositoryFactory())($container));
    }
}

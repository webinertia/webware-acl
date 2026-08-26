<?php

declare(strict_types=1);

namespace WebwareTest\Acl\InputFilter\Container;

use Laminas\InputFilter;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Acl\InputFilter\Container\RuleDataFilterFactory;
use Webware\Acl\InputFilter\RuleDataFilter;

#[CoversClass(RuleDataFilterFactory::class)]
final class RuleDataFilterFactoryTest extends TestCase
{
    #[Test]
    public function invokeBuildsFilter(): void
    {
        $factory   = InputFilter\Factory::new(new ServiceManager());
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                [InputFilter\Factory::class, $factory],
            ]);

        self::assertInstanceOf(RuleDataFilter::class, (new RuleDataFilterFactory())($container));
    }
}

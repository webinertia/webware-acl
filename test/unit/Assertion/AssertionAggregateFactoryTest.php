<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Assertion;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Webware\Acl\Assertion\AssertionAggregate;
use Webware\Acl\Assertion\AssertionAggregateFactory;
use Webware\Acl\AssertionManager;

#[CoversClass(AssertionAggregateFactory::class)]
final class AssertionAggregateFactoryTest extends TestCase
{
    private AssertionAggregateFactory $factory;

    private AssertionManager $assertionManager;

    #[Test]
    public function invokeBuildsAggregate(): void
    {
        $aggregate = ($this->factory)(['Ownership']);

        self::assertInstanceOf(AssertionAggregate::class, $aggregate);
        self::assertSame($this->assertionManager, $aggregate->getAssertionManager());
        self::assertSame(AssertionAggregate::MODE_AT_LEAST_ONE, $aggregate->getMode());
        self::assertSame(
            ['Ownership'],
            new ReflectionProperty(AssertionAggregate::class, 'assertions')->getValue($aggregate),
        );
    }

    #[Test]
    public function invokeReturnsNullForEmptyAliases(): void
    {
        self::assertNull(($this->factory)(null));
        self::assertNull(($this->factory)([]));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertionManager = new AssertionManager(new ServiceManager());
        $this->factory          = new AssertionAggregateFactory($this->assertionManager);
    }
}

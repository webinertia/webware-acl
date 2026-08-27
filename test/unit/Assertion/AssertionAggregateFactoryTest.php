<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Assertion;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Assertion\AssertionAggregate;
use Webware\Acl\Assertion\AssertionAggregateFactory;
use Webware\Acl\AssertionManager;

#[CoversClass(AssertionAggregateFactory::class)]
final class AssertionAggregateFactoryTest extends TestCase
{
    private AssertionAggregateFactory $factory;

    #[Test]
    public function invokeBuildsAggregate(): void
    {
        $aggregate = ($this->factory)(['Ownership']);

        self::assertInstanceOf(AssertionAggregate::class, $aggregate);
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

        $this->factory = new AssertionAggregateFactory(new AssertionManager(new ServiceManager()));
    }
}

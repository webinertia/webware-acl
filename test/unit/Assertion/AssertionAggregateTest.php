<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Assertion;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Assertion\AssertionAggregate;
use Webware\Acl\AssertionManager;

#[CoversClass(AssertionAggregate::class)]
final class AssertionAggregateTest extends TestCase
{
    #[Test]
    public function assertionManagerCanBeSetAndRead(): void
    {
        $aggregate = new AssertionAggregate();
        $manager   = new AssertionManager(new ServiceManager());

        self::assertSame($aggregate, $aggregate->setAssertionManager($manager));
        self::assertSame($manager, $aggregate->getAssertionManager());
    }

    #[Test]
    public function assertionManagerDefaultsToNull(): void
    {
        self::assertNull(new AssertionAggregate()->getAssertionManager());
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\AssertionManager;

#[CoversClass(AssertionManager::class)]
final class AssertionManagerTest extends TestCase
{
    #[Test]
    public function configureReturnsSelf(): void
    {
        $manager = new AssertionManager(new ServiceManager());

        self::assertSame($manager, $manager->configure([
            'aliases'   => ['Ownership' => OwnershipAssertion::class],
            'factories' => [OwnershipAssertion::class => OwnershipAssertion::class],
        ]));
    }

    #[Test]
    public function getAssertionOptionsReturnsAliasLabelValuePairs(): void
    {
        $manager = new AssertionManager(new ServiceManager());
        $manager->configure([
            'aliases'   => ['Ownership' => OwnershipAssertion::class],
            'factories' => [OwnershipAssertion::class => OwnershipAssertion::class],
        ]);

        self::assertSame(
            [['label' => 'Ownership', 'value' => 'Ownership']],
            $manager->getAssertionOptions(),
        );
    }

    #[Test]
    public function getAssertionOptionsReturnsEmptyArrayWhenNotConfigured(): void
    {
        $manager = new AssertionManager(new ServiceManager());

        self::assertSame([], $manager->getAssertionOptions());
    }
}

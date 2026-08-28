<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Assertion\OwnershipAssertion;

#[CoversClass(OwnershipAssertion::class)]
final class OwnershipAssertionTest extends TestCase
{
    #[Test]
    public function assertAllowsWhenOwnersMatch(): void
    {
        $assertion = new OwnershipAssertion();

        self::assertTrue($assertion->assert(
            $this->createStub(Acl::class),
            $this->proprietaryRole(42),
            $this->proprietaryResource(42),
        ));
    }

    #[Test]
    public function assertDeniesWhenBothOwnersAreNull(): void
    {
        $assertion = new OwnershipAssertion();

        self::assertFalse($assertion->assert(
            $this->createStub(Acl::class),
            $this->proprietaryRole(null),
            $this->proprietaryResource(null),
        ));
    }

    #[Test]
    public function assertDeniesWhenOwnersDiffer(): void
    {
        $assertion = new OwnershipAssertion();

        self::assertFalse($assertion->assert(
            $this->createStub(Acl::class),
            $this->proprietaryRole(1),
            $this->proprietaryResource(2),
        ));
    }

    #[Test]
    public function assertDeniesWhenResourceHasNoOwner(): void
    {
        $assertion = new OwnershipAssertion();

        self::assertFalse($assertion->assert(
            $this->createStub(Acl::class),
            $this->proprietaryRole(1),
            $this->proprietaryResource(null),
        ));
    }

    #[Test]
    public function assertDeniesWhenResourceIsNotProprietary(): void
    {
        $assertion = new OwnershipAssertion();

        self::assertFalse($assertion->assert(
            $this->createStub(Acl::class),
            $this->proprietaryRole(1),
            $this->createStub(ResourceInterface::class),
        ));
    }

    #[Test]
    public function assertDeniesWhenRoleIsNotProprietary(): void
    {
        $assertion = new OwnershipAssertion();

        self::assertFalse($assertion->assert(
            $this->createStub(Acl::class),
            $this->createStub(RoleInterface::class),
            $this->proprietaryResource(1),
        ));
    }

    #[Test]
    public function invokeReturnsNewInstance(): void
    {
        $assertion = new OwnershipAssertion();

        self::assertInstanceOf(OwnershipAssertion::class, $assertion());
    }

    private function proprietaryResource(int|string|null $ownerId): ResourceInterface
    {
        $resource = $this->createStubForIntersectionOfInterfaces([
            ResourceInterface::class,
            ProprietaryInterface::class,
        ]);
        $resource->method('getOwnerId')->willReturn($ownerId);

        return $resource;
    }

    private function proprietaryRole(int|string|null $ownerId): RoleInterface
    {
        $role = $this->createStubForIntersectionOfInterfaces([RoleInterface::class, ProprietaryInterface::class]);
        $role->method('getOwnerId')->willReturn($ownerId);

        return $role;
    }
}

<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Validator;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\AssertionManager;
use Webware\Acl\Validator\Assertion;

#[CoversClass(Assertion::class)]
final class AssertionTest extends TestCase
{
    #[Test]
    public function arrayOfKnownAssertionsIsValid(): void
    {
        self::assertTrue($this->validator()->isValid(['Ownership']));
    }

    #[Test]
    public function arrayWithUnknownAssertionIsInvalid(): void
    {
        self::assertFalse($this->validator()->isValid(['Ownership', 'Nope']));
    }

    #[Test]
    public function emptyArrayElementIsInvalid(): void
    {
        self::assertFalse($this->validator()->isValid(['']));
    }

    #[Test]
    public function emptyStringIsTreatedAsNull(): void
    {
        self::assertTrue($this->validator(['nullable' => true])->isValid(''));
    }

    #[Test]
    public function knownAssertionIsValid(): void
    {
        self::assertTrue($this->validator()->isValid('Ownership'));
    }

    #[Test]
    public function nonNullableNullIsInvalid(): void
    {
        self::assertFalse($this->validator()->isValid(null));
    }

    #[Test]
    public function nonStringNonArrayValueIsRejected(): void
    {
        self::assertFalse($this->validator()->isValid(123));
    }

    #[Test]
    public function nullableNullIsValid(): void
    {
        self::assertTrue($this->validator(['nullable' => true])->isValid(null));
    }

    #[Test]
    public function nullOptionsDefaultToNonNullable(): void
    {
        self::assertFalse(new Assertion($this->assertionManager(), null)->isValid(null));
    }

    #[Test]
    public function unknownAssertionIsInvalid(): void
    {
        self::assertFalse($this->validator()->isValid('Nope'));
    }

    private function assertionManager(): AssertionManager
    {
        $manager = new AssertionManager(new ServiceManager());
        $manager->configure([
            'aliases'   => ['Ownership' => OwnershipAssertion::class],
            'factories' => [OwnershipAssertion::class => OwnershipAssertion::class],
        ]);

        return $manager;
    }

    /**
     * @param array{nullable?: bool} $options
     */
    private function validator(array $options = ['nullable' => false]): Assertion
    {
        return new Assertion($this->assertionManager(), $options);
    }
}

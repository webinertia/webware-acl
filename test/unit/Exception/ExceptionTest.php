<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException as SplRuntimeException;
use Webware\Acl\Exception\ExceptionInterface;
use Webware\Acl\Exception\InvalidConfigException;
use Webware\Acl\Exception\RuntimeException;

#[CoversClass(RuntimeException::class)]
#[CoversClass(InvalidConfigException::class)]
final class ExceptionTest extends TestCase
{
    #[Test]
    public function forAclAddResourceBuildsRuntimeException(): void
    {
        $exception = RuntimeException::forAclAddResource();

        self::assertInstanceOf(SplRuntimeException::class, $exception);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
    }

    #[Test]
    public function invalidConfigExtendsInvalidArgumentException(): void
    {
        self::assertInstanceOf(\InvalidArgumentException::class, new InvalidConfigException());
        self::assertInstanceOf(ExceptionInterface::class, new InvalidConfigException());
    }
}

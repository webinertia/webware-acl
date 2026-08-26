<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use Laminas\Permissions\Acl\Acl as LaminasAcl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;
use Webware\Acl\RuleType;

#[CoversClass(RuleType::class)]
final class RuleTypeTest extends TestCase
{
    #[Test]
    public function allowCaseValue(): void
    {
        self::assertSame('Allow', RuleType::Allow->value);
    }

    #[Test]
    public function allowMapsToAclAllowConstant(): void
    {
        self::assertSame(LaminasAcl::TYPE_ALLOW, RuleType::Allow->toAclConstant());
    }

    #[Test]
    public function denyCaseValue(): void
    {
        self::assertSame('Deny', RuleType::Deny->value);
    }

    #[Test]
    public function denyMapsToAclDenyConstant(): void
    {
        self::assertSame(LaminasAcl::TYPE_DENY, RuleType::Deny->toAclConstant());
    }

    #[Test]
    public function fromAcceptsAllowValue(): void
    {
        self::assertSame(RuleType::Allow, RuleType::from('Allow'));
    }

    #[Test]
    public function fromAcceptsDenyValue(): void
    {
        self::assertSame(RuleType::Deny, RuleType::from('Deny'));
    }

    #[Test]
    public function fromRejectsUnknownValue(): void
    {
        $this->expectException(ValueError::class);

        RuleType::from('Grant');
    }
}

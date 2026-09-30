<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Seed;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\RuleSeedIndex;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;

#[CoversClass(RuleSeedIndex::class)]
#[CoversMethod(RuleSeedIndex::class, '__construct')]
#[CoversMethod(RuleSeedIndex::class, 'from')]
#[CoversMethod(RuleSeedIndex::class, 'isRoute')]
#[CoversMethod(RuleSeedIndex::class, 'isAnchor')]
#[CoversMethod(RuleSeedIndex::class, 'isReferenced')]
#[CoversMethod(RuleSeedIndex::class, 'isDeclaredAnchor')]
#[CoversMethod(RuleSeedIndex::class, 'isResolvableParent')]
final class RuleSeedIndexTest extends TestCase
{
    /** @var list<string> */
    private const array ROUTES = ['admin.acl', 'admin.acl.role.read'];

    #[Test]
    public function acceptsARouteOrReferencedNodeAsADeclaredAnchor(): void
    {
        $index = $this->index();

        self::assertTrue($index->isDeclaredAnchor('admin.acl'));
        self::assertTrue($index->isDeclaredAnchor('user'));
        self::assertFalse($index->isDeclaredAnchor('orphan'));
    }

    #[Test]
    public function acceptsARouteOrRootRowAsAParent(): void
    {
        $index = $this->index();

        self::assertTrue($index->isResolvableParent('admin.acl'));
        self::assertTrue($index->isResolvableParent('user'));
        self::assertFalse($index->isResolvableParent('user.missing'));
    }

    #[Test]
    public function knowsDeclaredRootRows(): void
    {
        $index = $this->index();

        self::assertTrue($index->isAnchor('user'));
        self::assertFalse($index->isAnchor('admin.acl'));
    }

    #[Test]
    public function knowsNodesSomethingHangsFrom(): void
    {
        $index = $this->index();

        self::assertTrue($index->isReferenced('user'));
        self::assertFalse($index->isReferenced('admin.acl'));
    }

    #[Test]
    public function knowsRegisteredRouteNames(): void
    {
        $index = $this->index();

        self::assertTrue($index->isRoute('admin.acl.role.read'));
        self::assertFalse($index->isRoute('user'));
    }

    private function index(): RuleSeedIndex
    {
        return RuleSeedIndex::from(
            ruleSeeds : [
                new RuleSeed(
                    type      : RuleType::Allow,
                    roleId    : 'Guest',
                    resourceId: 'user',
                ),
                new RuleSeed(
                    type            : RuleType::Allow,
                    roleId          : 'Guest',
                    resourceId      : 'user.session.read',
                    parentResourceId: 'user',
                ),
            ],
            routeNames: self::ROUTES,
        );
    }
}

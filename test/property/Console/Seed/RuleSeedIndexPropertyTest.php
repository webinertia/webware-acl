<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebwareTest\Acl\Property\Console\Seed;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\RuleSeedIndex;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;

use function array_unique;
use function array_values;
use function count;
use function Eris\Generator\elements;
use function Eris\Generator\map;
use function Eris\Generator\seq;
use function in_array;

/**
 * Resolvability properties of RuleSeedIndex over generated seed sets.
 *
 * The example suite next door pins a handful of node names to expected
 * booleans. These state the relationship the index exists to answer, using the
 * seed set itself as the oracle rather than a second copy of the lookup.
 *
 * The divergence this guards against is the one the class documentation calls
 * out: two producers of the same fact. A change to what counts as resolvable
 * that is not mirrored where the index is consumed is invisible to a test that
 * only checks fixed names.
 */
#[CoversClass(RuleSeedIndex::class)]
#[CoversMethod(RuleSeedIndex::class, 'from')]
#[CoversMethod(RuleSeedIndex::class, 'isRoute')]
#[CoversMethod(RuleSeedIndex::class, 'isResolvableParent')]
final class RuleSeedIndexPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * @var list<string>
     */
    private const array ROUTE_POOL = [
        'admin.acl',
        'admin.acl.role.read',
        'admin.acl.rule.read',
        'user.manager.list',
        'user.session.read',
    ];

    /**
     * @var list<string>
     */
    private const array ANCHOR_POOL = [
        'user',
        'admin',
        'user.manager',
    ];

    /**
     * @return list<string>
     */
    private static function candidates(): array
    {
        return [...self::ROUTE_POOL, ...self::ANCHOR_POOL, 'absent.node'];
    }

    /**
     * Builds a set satisfying the documented contract: each anchor is declared
     * as a root and given one leaf child naming a route, which is what makes an
     * abstract anchor legal.
     *
     * @param list<string> $routes
     * @param list<string> $anchors
     *
     * @return list<RuleSeed>
     */
    private static function wellFormedSeeds(array $routes, array $anchors): array
    {
        if ([] === $routes) {
            return [];
        }

        $seeds      = [];
        $routeCount = count($routes);

        foreach ($anchors as $index => $anchor) {
            $seeds[] = new RuleSeed(
                type      : RuleType::Allow,
                roleId    : 'Developer',
                resourceId: $anchor,
            );

            $seeds[] = new RuleSeed(
                type            : RuleType::Allow,
                roleId          : 'Developer',
                resourceId      : $routes[$index % $routeCount],
                parentResourceId: $anchor,
            );
        }

        return $seeds;
    }

    /**
     * Every parent named by a well-formed set resolves, which is the condition
     * ACL load enforces.
     *
     * This is the index's reason to exist, expressed without restating how the
     * lookup is implemented: whatever the set contains, a parent it names must
     * be resolvable. A future change that narrows what counts as resolvable is
     * found here even though every hand-picked node in the example suite still
     * passes.
     */
    #[Test]
    public function everyParentInAWellFormedSetResolves(): void
    {
        $this->forAll(
            $this->uniqueSubsetOf(self::ROUTE_POOL),
            $this->uniqueSubsetOf(self::ANCHOR_POOL),
        )
            ->then(static function (array $routes, array $anchors): void {
                $seeds = self::wellFormedSeeds($routes, $anchors);
                $index = RuleSeedIndex::from(
                    ruleSeeds : $seeds,
                    routeNames: $routes,
                );

                foreach ($seeds as $seed) {
                    $parent = $seed->parentResourceId;

                    if (null === $parent) {
                        continue;
                    }

                    self::assertTrue(
                        $index->isResolvableParent($parent),
                        'A parent named by the seed set did not resolve.',
                    );
                }
            });
    }

    /**
     * `isRoute` agrees with the route list the index was built from, for names
     * in either pool and for names in neither.
     *
     * The index is the only place a resource id is matched against the router,
     * so a membership test that drifted from the list it was given would let a
     * leaf row through that no request can ever match.
     */
    #[Test]
    public function isRouteAgreesWithTheRouteList(): void
    {
        $this->forAll($this->uniqueSubsetOf(self::ROUTE_POOL))
            ->then(static function (array $routes): void {
                $index = RuleSeedIndex::from(
                    ruleSeeds : [],
                    routeNames: $routes,
                );

                foreach (self::candidates() as $candidate) {
                    self::assertSame(
                        in_array($candidate, $routes, strict: true),
                        $index->isRoute($candidate),
                        "isRoute disagreed with the route list for {$candidate}",
                    );
                }
            });
    }

    /**
     * @param list<string> $pool
     *
     * @return Generator
     */
    private function uniqueSubsetOf(array $pool): Generator
    {
        return map(
            static fn(array $names): array => array_values(array_unique($names)),
            seq(elements(...$pool)),
        );
    }
}

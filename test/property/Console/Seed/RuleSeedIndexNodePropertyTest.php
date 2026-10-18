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

use function array_fill_keys;
use function array_key_exists;
use function array_unique;
use function array_values;
use function count;
use function Eris\Generator\elements;
use function Eris\Generator\map;
use function Eris\Generator\seq;

/**
 * Node and composition properties of RuleSeedIndex.
 *
 * `isResolvableParent` is route-or-anchor and `isDeclaredAnchor` is
 * route-or-referenced. They answer different questions, and the difference
 * matters: a node something hangs from is worth declaring even when it is
 * unresolvable, because that is how a typo in an anchor becomes visible. A
 * change that collapsed them into one predicate would erase that signal, and a
 * test that only checked fixed node names would not notice.
 */
#[CoversClass(RuleSeedIndex::class)]
#[CoversMethod(RuleSeedIndex::class, 'isAnchor')]
#[CoversMethod(RuleSeedIndex::class, 'isReferenced')]
#[CoversMethod(RuleSeedIndex::class, 'isDeclaredAnchor')]
#[CoversMethod(RuleSeedIndex::class, 'isResolvableParent')]
final class RuleSeedIndexNodePropertyTest extends TestCase
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
     * @param list<RuleSeed> $seeds
     *
     * @return list<string>
     */
    private static function parentIds(array $seeds): array
    {
        $ids = [];

        foreach ($seeds as $seed) {
            if (null === $seed->parentResourceId) {
                continue;
            }

            $ids[] = $seed->parentResourceId;
        }

        return $ids;
    }

    /**
     * @param list<RuleSeed> $seeds
     *
     * @return list<string>
     */
    private static function resourceIdsWithoutParent(array $seeds): array
    {
        $ids = [];

        foreach ($seeds as $seed) {
            if (null !== $seed->parentResourceId) {
                continue;
            }

            $ids[] = $seed->resourceId;
        }

        return $ids;
    }

    /**
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
     * The two compositions stay consistent with the predicates they compose.
     *
     * Each is checked against its parts rather than against a literal, so the
     * assertion survives a change to what the parts mean while still catching a
     * composition that stopped matching them.
     */
    #[Test]
    public function compositionsAgreeWithTheirParts(): void
    {
        $this->forAll(
            $this->uniqueSubsetOf(self::ROUTE_POOL),
            $this->uniqueSubsetOf(self::ANCHOR_POOL),
        )
            ->then(static function (array $routes, array $anchors): void {
                $index = RuleSeedIndex::from(
                    ruleSeeds : self::wellFormedSeeds($routes, $anchors),
                    routeNames: $routes,
                );

                foreach (self::candidates() as $candidate) {
                    self::assertSame(
                        $index->isRoute($candidate) || $index->isAnchor($candidate),
                        $index->isResolvableParent($candidate),
                        "isResolvableParent drifted from its parts for {$candidate}",
                    );

                    self::assertSame(
                        $index->isRoute($candidate) || $index->isReferenced($candidate),
                        $index->isDeclaredAnchor($candidate),
                        "isDeclaredAnchor drifted from its parts for {$candidate}",
                    );
                }
            });
    }

    /**
     * The node predicates agree with facts derived from the seed set by hand,
     * not with one another.
     *
     * Comparing a predicate against another predicate lets a matching pair of
     * bugs pass. Deriving the expected answer from the seeds instead means any
     * single change is visible here.
     */
    #[Test]
    public function nodePredicatesAgreeWithTheSeedSet(): void
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

                $expectedAnchors = array_fill_keys(
                    keys : self::resourceIdsWithoutParent($seeds),
                    value: true,
                );

                $expectedParents = array_fill_keys(
                    keys : self::parentIds($seeds),
                    value: true,
                );

                foreach (self::candidates() as $candidate) {
                    self::assertSame(
                        array_key_exists($candidate, $expectedAnchors),
                        $index->isAnchor($candidate),
                        "isAnchor disagreed with the seed set for {$candidate}",
                    );

                    self::assertSame(
                        array_key_exists($candidate, $expectedParents),
                        $index->isReferenced($candidate),
                        "isReferenced disagreed with the seed set for {$candidate}",
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

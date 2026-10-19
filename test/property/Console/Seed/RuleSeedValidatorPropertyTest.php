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
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;

use function abs;
use function array_unique;
use function array_values;
use function count;
use function Eris\Generator\elements;
use function Eris\Generator\int;
use function Eris\Generator\map;
use function Eris\Generator\seq;

/**
 * Properties of RuleSeedValidator over generated seed sets.
 *
 * The example suite next door fixes a handful of shapes and asserts exact
 * message text. These assert the rule the validator exists to enforce: a set
 * built the way the contract describes produces no violations at all, at any
 * size or arrangement.
 *
 * The failure this guards against is a check that only holds for the shapes
 * somebody thought to write down. A validator that passes three hand-built
 * trees and rejects a fourth well-formed one is precisely the silent failure
 * the class was written to prevent, and it would surface at ACL load time where
 * the cause is no longer visible.
 */
#[CoversClass(RuleSeedValidator::class)]
#[CoversMethod(RuleSeedValidator::class, 'violations')]
final class RuleSeedValidatorPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Registered route names. A leaf must name one of these, because that is
     * the only thing a check at request time can match.
     *
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
     * Abstract segment names that answer to no route of their own. Such a
     * segment is a legal anchor only when something hangs from it.
     *
     * @var list<string>
     */
    private const array ANCHOR_POOL = [
        'user',
        'admin',
        'user.manager',
    ];

    /**
     * Every role webware provides, so a generated set is not all one role.
     *
     * @var list<string>
     */
    private const array ROLE_POOL = [
        'Guest',
        'Member',
        'Administrator',
        'Developer',
    ];

    /**
     * Builds a seed set satisfying the documented contract for the given routes
     * and abstract anchors.
     * Each anchor is declared as a root and given one leaf child, which is what
     * makes it a legal anchor: an abstract segment is valid only when something
     * hangs from it. A route used as a root anchor is legal on its own, since it
     * answers to a route of its own.
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
        $roles      = self::ROLE_POOL;

        foreach ($anchors as $index => $anchor) {
            $roleId = $roles[$index % count($roles)];

            $seeds[] = new RuleSeed(
                type      : RuleType::Allow,
                roleId    : $roleId,
                resourceId: $anchor,
            );

            $seeds[] = new RuleSeed(
                type            : RuleType::Allow,
                roleId          : $roleId,
                resourceId      : $routes[$index % $routeCount],
                parentResourceId: $anchor,
            );
        }

        if ([] === $seeds) {
            $seeds[] = new RuleSeed(
                type      : RuleType::Allow,
                roleId    : 'Developer',
                resourceId: $routes[0],
            );
        }

        return $seeds;
    }

    /**
     * The same (role, resource) pair claimed twice with different values is
     * always reported, and claimed twice with identical values never is.
     *
     * Two providers describing one pair differently is a conflict worth
     * surfacing; two describing it identically is not. Identity comparison
     * cannot tell those apart, so the class compares fields, and this pins that
     * distinction across every generated route and role.
     */
    #[Test]
    public function conflictingClaimsAreReportedAndIdenticalClaimsAreNot(): void
    {
        $this->forAll(
            $this->uniqueSubsetOf(self::ROUTE_POOL),
            elements(...self::ROLE_POOL),
        )
            ->then(static function (array $routes, string $roleId): void {
                if ([] === $routes) {
                    return;
                }

                $validator = new RuleSeedValidator();
                $resource  = $routes[0];

                $claim = static fn(RuleType $type): RuleSeed => new RuleSeed(
                    type      : $type,
                    roleId    : $roleId,
                    resourceId: $resource,
                );

                self::assertSame(
                    [],
                    $validator->violations(
                        ruleSeeds : [$claim(RuleType::Allow), $claim(RuleType::Allow)],
                        routeNames: $routes,
                    ),
                    'Identical claims were reported as a conflict.',
                );

                $conflicts = $validator->violations(
                    ruleSeeds : [$claim(RuleType::Allow), $claim(RuleType::Deny)],
                    routeNames: $routes,
                );

                self::assertCount(1, $conflicts);
                self::assertStringContainsString('claimed twice with different values', $conflicts[0]);
            });
    }

    /**
     * Every defect in a set is reported, not only the first one found.
     *
     * The validator exists to report all the reasons a seed set is unacceptable
     * in a single pass, so an operator fixes them together instead of
     * rediscovering one per run. That makes each check's flow control part of
     * the contract: a `break` where a `continue` belongs silently truncates the
     * report after the first defect in that branch.
     *
     * A single-row case cannot express this, which is why it is a property. The
     * generated set always contains a self-parented row followed by rows with
     * independent defects, so skipping the rest of the list is observable as a
     * missing violation.
     */
    #[Test]
    public function everyDefectIsReportedNotJustTheFirst(): void
    {
        $this->forAll($this->uniqueSubsetOf(self::ROUTE_POOL))
            ->then(static function (array $routes): void {
                if ([] === $routes) {
                    return;
                }

                $validator = new RuleSeedValidator();
                $resource  = $routes[0];

                $selfParented = new RuleSeed(
                    type            : RuleType::Allow,
                    roleId          : 'Developer',
                    resourceId      : $resource,
                    parentResourceId: $resource,
                );

                $unknownLeaf = new RuleSeed(
                    type            : RuleType::Allow,
                    roleId          : 'Guest',
                    resourceId      : "{$resource}.unknown",
                    parentResourceId: $resource,
                );

                $violations = $validator->violations(
                    ruleSeeds : [$selfParented, $unknownLeaf],
                    routeNames: $routes,
                );

                self::assertCount(
                    2,
                    $violations,
                    'A defect after the first was not reported.',
                );

                self::assertStringContainsString('names itself as its parent', $violations[0]);
                self::assertStringContainsString('is not a registered route name', $violations[1]);
            });
    }

    /**
     * A row naming itself as its parent is always rejected, and is reported as
     * that defect alone.
     *
     * The check runs before the route lookup and short-circuits, so a
     * self-parented row does not also report an unregistered route. Collapsing
     * the two would send a reader looking for a missing route when the real
     * defect is circular topology.
     */
    #[Test]
    public function selfParentedRowsAreAlwaysRejected(): void
    {
        $this->forAll(
            $this->uniqueSubsetOf(self::ROUTE_POOL),
            $this->uniqueSubsetOf(self::ANCHOR_POOL),
        )
            ->then(static function (array $routes, array $anchors): void {
                $names = array_values(array_unique([...$routes, ...$anchors]));

                if ([] === $names) {
                    return;
                }

                $validator = new RuleSeedValidator();
                $resource  = $names[0];

                $violations = $validator->violations(
                    ruleSeeds : [
                        new RuleSeed(
                            type            : RuleType::Allow,
                            roleId          : 'Developer',
                            resourceId      : $resource,
                            parentResourceId: $resource,
                        ),
                    ],
                    routeNames: $routes,
                );

                self::assertCount(1, $violations);
                self::assertStringContainsString('names itself as its parent', $violations[0]);
            });
    }

    /**
     * A row whose parent resolves to no node is always rejected, whatever the
     * row itself names.
     *
     * This is the defect that makes ACL load throw, so it has to be caught
     * before anything is written. The generated parent is deliberately absent
     * from both pools, so resolution cannot succeed by coincidence.
     */
    #[Test]
    public function unresolvableParentsAreAlwaysRejected(): void
    {
        $this->forAll(
            $this->uniqueSubsetOf(self::ROUTE_POOL),
            map(
                static fn(int $suffix): string => 'absent.parent.' . abs($suffix),
                int(),
            ),
        )
            ->then(static function (array $routes, string $absentParent): void {
                if ([] === $routes) {
                    return;
                }

                $validator = new RuleSeedValidator();

                $violations = $validator->violations(
                    ruleSeeds : [
                        new RuleSeed(
                            type            : RuleType::Allow,
                            roleId          : 'Developer',
                            resourceId      : $routes[0],
                            parentResourceId: $absentParent,
                        ),
                    ],
                    routeNames: $routes,
                );

                self::assertCount(1, $violations);
                self::assertStringContainsString(
                    'is neither a registered route name nor an anchor node',
                    $violations[0],
                );
            });
    }

    /**
     * A seed set assembled the way the contract describes always validates.
     *
     * Shorter pools are included on purpose: an empty set must pass, and a tree
     * with one anchor and one leaf must pass. Those are the boundaries a
     * hand-written suite tends to skip.
     */
    #[Test]
    public function wellFormedSeedSetsHaveNoViolations(): void
    {
        $this->forAll(
            $this->uniqueSubsetOf(self::ROUTE_POOL),
            $this->uniqueSubsetOf(self::ANCHOR_POOL),
        )
            ->then(static function (array $routes, array $anchors): void {
                $validator = new RuleSeedValidator();

                self::assertSame(
                    [],
                    $validator->violations(
                        ruleSeeds : self::wellFormedSeeds($routes, $anchors),
                        routeNames: $routes,
                    ),
                    'A well-formed seed set reported violations.',
                );
            });
    }

    /**
     * An unordered subset of the pool with no repeats, so generated sets differ
     * only in which names they carry and how many.
     *
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

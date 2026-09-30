<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Seed;

use Webware\Core\Acl\RuleSeed;

use function sprintf;

/**
 * Cross-provider integrity checks for a collected set of rule seeds.
 *
 * These run before anything is written, because both failure modes they detect
 * are fatal later, at ACL load time, where the cause is no longer visible:
 *
 * - a `resourceId` that answers to no registered route is inert — it grants
 *   nothing and denies nothing, silently;
 * - a `parentResourceId` that resolves to no node makes the ACL throw while
 *   building its resource tree.
 *
 * The checks are deliberately structural rather than clever. A leaf row — one
 * that names a parent — must name a registered route, because that is the only
 * thing a check at request time can match. An anchor node may name a route (the
 * index route of an admin area) or an abstract segment such as `user`, but it
 * must be something, either a route name or the parent of at least one seed, so
 * that a typo cannot slip through as a node nothing hangs from.
 *
 * @api
 */
final readonly class RuleSeedValidator
{
    /**
     * @param list<RuleSeed> $ruleSeeds
     * @param list<string>   $routeNames
     *
     * @return list<string> human-readable violations, in seed order
     */
    public function violations(array $ruleSeeds, array $routeNames): array
    {
        $index = RuleSeedIndex::from(
            ruleSeeds : $ruleSeeds,
            routeNames: $routeNames,
        );

        $violations = [];
        $seen       = [];

        foreach ($ruleSeeds as $ruleSeed) {
            $resourceId = $ruleSeed->resourceId;
            $parentId   = $ruleSeed->parentResourceId;

            $previous = $seen[$ruleSeed->roleId][$resourceId] ?? null;

            if (null !== $previous && ! $previous->equals(other: $ruleSeed)) {
                $violations[] = sprintf(
                    'Role "%s" and resource "%s" are claimed twice with different values.',
                    $ruleSeed->roleId,
                    $resourceId,
                );
            }

            $seen[$ruleSeed->roleId][$resourceId] = $ruleSeed;

            if (null === $parentId) {
                if (! $index->isDeclaredAnchor($resourceId)) {
                    $violations[] = sprintf(
                        'Anchor node "%s" is neither a registered route name nor the parent of any seed.',
                        $resourceId,
                    );
                }

                continue;
            }

            if ($resourceId === $parentId) {
                $violations[] = sprintf('Resource "%s" names itself as its parent.', $resourceId);

                continue;
            }

            if (! $index->isRoute($resourceId)) {
                $violations[] = sprintf('Resource "%s" is not a registered route name.', $resourceId);
            }

            if (! $index->isResolvableParent($parentId)) {
                $violations[] = sprintf(
                    'Parent "%s" of resource "%s" is neither a registered route name nor an anchor node.',
                    $parentId,
                    $resourceId,
                );
            }
        }

        return $violations;
    }
}

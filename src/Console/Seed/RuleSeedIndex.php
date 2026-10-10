<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Seed;

use Webware\Core\Acl\RuleSeed;

use function array_fill_keys;
use function array_key_exists;

/**
 * What a collected seed set contributes to the ACL resource tree.
 *
 * Rows describe a tree, not a flat list: `resourceId` is a node, and
 * `parentResourceId` places it. A node is one of two things - a registered route
 * name, which is what an authorization check can match, or an anchor such as
 * `user` or `admin.acl`, which answers to no route of its own and exists so that
 * a role can be granted a whole subtree in one row.
 *
 * Both failure modes this index exists to expose are silent at runtime in
 * different ways, which is why they are worth checking separately: a node that
 * answers to no route grants nothing, while a parent that resolves to no node
 * makes ACL load throw.
 *
 * @api
 */
final readonly class RuleSeedIndex
{
    /**
     * @param array<string, true> $routes
     * @param array<string, true> $anchors
     * @param array<string, true> $parents
     */
    private function __construct(
        private array $routes,
        private array $anchors,
        private array $parents,
    ) {}

    /**
     * @param list<RuleSeed> $ruleSeeds
     * @param list<string>   $routeNames
     */
    public static function from(array $ruleSeeds, array $routeNames): self
    {
        $anchors = [];
        $parents = [];

        foreach ($ruleSeeds as $ruleSeed) {
            if (null === $ruleSeed->parentResourceId) {
                $anchors[$ruleSeed->resourceId] = true;

                continue;
            }

            $parents[$ruleSeed->parentResourceId] = true;
        }

        return new self(
            routes : array_fill_keys(
                keys : $routeNames,
                value: true,
            ),
            anchors: $anchors,
            parents: $parents,
        );
    }

    /**
     * A root row: a node declared with no parent of its own.
     */
    public function isAnchor(string $resourceId): bool
    {
        return array_key_exists(
            key  : $resourceId,
            array: $this->anchors,
        );
    }

    /**
     * A node worth declaring: it either answers to a route or something hangs from it.
     * Failing this is how a typo in an anchor shows up, since an anchor has no route
     * to match against.
     */
    public function isDeclaredAnchor(string $resourceId): bool
    {
        return $this->isRoute($resourceId) || $this->isReferenced($resourceId);
    }

    /**
     * A node that at least one seed hangs from.
     */
    public function isReferenced(string $resourceId): bool
    {
        return array_key_exists(
            key  : $resourceId,
            array: $this->parents,
        );
    }

    /**
     * A node the tree can attach a child to: a route name or a declared root.
     * This is exactly what the ACL requires at load time, where a parent that
     * resolves to nothing throws.
     */
    public function isResolvableParent(string $resourceId): bool
    {
        return $this->isRoute($resourceId) || $this->isAnchor($resourceId);
    }

    /**
     * A registered route name - the only kind of node a request-time check can match.
     */
    public function isRoute(string $resourceId): bool
    {
        return array_key_exists(
            key  : $resourceId,
            array: $this->routes,
        );
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl;

use Override;
use Webware\Acl\Container\Configuration;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Acl\RuleType;
use Webware\Core\Role;

use function rtrim;

/**
 * The rules webware-acl itself owns: the ACL manager, granted to Developer.
 *
 * The anchor is the admin index route (`admin.acl`), a real route name and the
 * unit of grant; its children name it as their parent so they inherit from it
 * and nest beneath it in the administration screens.
 *
 * @internal
 */
final readonly class RuleSeeds implements RuleSeedProviderInterface
{
    private const array CHILDREN = [
        'role.read',
        'role.add.modal',
        'role.edit.modal',
        'role.create',
        'role.update',
        'role.delete',
        'rule.create',
        'rule.update',
        'rule.delete',
        'rule.delete.modal',
    ];

    /**
     * @return list<RuleSeed>
     */
    #[Override]
    public function ruleSeeds(string $adminName): array
    {
        $prefix = Configuration::getAdminRouteNamePrefix($adminName);
        $anchor = rtrim(
            string    : $prefix,
            characters: '.',
        );

        $seeds = [
            new RuleSeed(
                type      : RuleType::Allow,
                roleId    : Role::Developer->value,
                resourceId: $anchor,
            ),
        ];

        foreach (self::CHILDREN as $child) {
            $seeds[] = new RuleSeed(
                type            : RuleType::Allow,
                roleId          : Role::Developer->value,
                resourceId      : $prefix . $child,
                parentResourceId: $anchor,
            );
        }

        return $seeds;
    }
}

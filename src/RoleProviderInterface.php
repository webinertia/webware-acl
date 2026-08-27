<?php

declare(strict_types=1);

namespace Webware\Acl;

use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * @api
 */
interface RoleProviderInterface
{
    public function getRole(): RoleInterface;
}

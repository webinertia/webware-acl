<?php

declare(strict_types=1);

namespace Webware\Acl\Query;

use Webware\MessageBus\Query\QueryInterface;

/**
 * Fetch the ACL role registry built from persisted roles.
 */
final readonly class FetchAclRoleRegistry implements QueryInterface {}

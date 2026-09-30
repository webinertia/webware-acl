<?php

declare(strict_types=1);

namespace Webware\Acl\Repository;

use PhpDb\SchemaInterface;

enum Schema: string implements SchemaInterface
{
    case Roles = 'acl_role';
    case Rules = 'acl_rule';
}

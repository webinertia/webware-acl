<?php

declare(strict_types=1);

namespace Webware\Acl\Repository;

use Override;
use PhpDb\Sql\TableIdentifier;
use Webware\Core\SchemaInterface;

enum Schema: string implements SchemaInterface
{
    case Roles = 'acl_role';
    case Rules = 'acl_rule';

    #[Override]
    public function table(): TableIdentifier
    {
        return new TableIdentifier($this->value);
    }
}

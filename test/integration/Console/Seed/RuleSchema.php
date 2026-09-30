<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl\Console\Seed;

use PhpDb\SchemaInterface;

/**
 * The rule table name, declared the same way the package that owns the DDL
 * declares it, so the integration suite can exercise the seeder against the real
 * shape without touching the live table.
 */
enum RuleSchema: string implements SchemaInterface
{
    case Rules = 'acl_rule';
}

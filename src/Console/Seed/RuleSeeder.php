<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Seed;

use JsonException;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Exception\ExceptionInterface as PhpDbException;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Sql\InsertIgnore;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Update;
use Webware\Core\Acl\RuleSeed;

use function count;
use function in_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * The single writer for ACL rule seeds.
 *
 * The table is injected as a {@see TableIdentifier} rather than named here: the
 * table belongs to the package that owns its DDL, and the schema factory that
 * resolves prefixes and schemas belongs to the caller. That is what lets
 * webware-usermanager contribute policy without depending on webware-acl - it
 * publishes seeds, and whoever runs the seeding command writes them.
 *
 * Seeding is idempotent and authoritative. The pair (roleId, resourceId) is the
 * table's unique key, so the row is inserted when absent and its value columns
 * are then set unconditionally: a changed seed replaces what is stored, while the
 * row id stays stable for the administration screens that address rules by id.
 *
 * A missing table is reported, never thrown. Seeding may run before the owner's
 * schema exists - that is a warning for the command to print, not a failure that
 * stops an installer.
 *
 * @api
 */
final readonly class RuleSeeder
{
    public function __construct(
        private AdapterInterface $adapter,
        private TableIdentifier $table,
        private MetadataInterface $metadata,
        private RuleSeedValidator $validator,
    ) {}

    /**
     * @param list<string> $assertions
     *
     * @throws JsonException
     */
    private static function assertions(array $assertions): ?string
    {
        return [] === $assertions
            ? null
            : json_encode(
                value: $assertions,
                flags: JSON_THROW_ON_ERROR,
            );
    }

    public function ruleTableExists(): bool
    {
        return in_array(
            needle  : $this->table->getTable(),
            haystack: $this->metadata->getTableNames(schema: $this->table->getSchema()),
            strict  : true,
        );
    }

    /**
     * Validate the whole collected set, then write it.
     *
     * Nothing is written when the set is invalid: a partially applied seed is worse
     * than an unchanged one, because the rows that did land still look authorised.
     *
     * @param list<RuleSeed> $ruleSeeds
     * @param list<string>   $routeNames the registered route names the seeds are checked against
     *
     * @throws JsonException If an assertion list cannot be encoded.
     * @throws PhpDbException If a statement cannot be built or executed.
     */
    public function seed(array $ruleSeeds, array $routeNames): SeedResult
    {
        $violations = $this->validator->violations(
            ruleSeeds : $ruleSeeds,
            routeNames: $routeNames,
        );

        if ([] !== $violations) {
            return new SeedResult(violations: $violations);
        }

        if (! $this->ruleTableExists()) {
            return new SeedResult(tableMissing: true);
        }

        $sql = new Sql($this->adapter);

        foreach ($ruleSeeds as $ruleSeed) {
            $this->write(
                sql     : $sql,
                ruleSeed: $ruleSeed,
            );
        }

        return new SeedResult(seeded: count($ruleSeeds));
    }

    /**
     * @throws JsonException
     * @throws PhpDbException
     */
    private function write(Sql $sql, RuleSeed $ruleSeed): void
    {
        $identity = [
            'roleId'     => $ruleSeed->roleId,
            'resourceId' => $ruleSeed->resourceId,
        ];

        $values = [
            'type'             => $ruleSeed->type->value,
            'assertions'       => self::assertions($ruleSeed->assertions),
            'parentResourceId' => $ruleSeed->parentResourceId,
        ];

        // The seed is authoritative, and the row id stays stable: insert when the
        // pair is absent, then set the value columns unconditionally so a changed
        // seed replaces what is stored.
        $sql->prepareStatementForSqlObject(
            new InsertIgnore(table: $this->table)->values($identity + $values),
        )->execute();

        $sql->prepareStatementForSqlObject(
            new Update(table: $this->table)->set($values)->where($identity),
        )->execute();
    }
}

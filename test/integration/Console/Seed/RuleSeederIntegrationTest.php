<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl\Console\Seed;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql;
use PhpDb\SchemaFactory;
use PhpDb\SchemaInterface;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\WebwareProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Console\Seed\RuleSeeder;
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;

use function getenv;
use function is_array;
use function json_encode;

/**
 * Exercises the write path against a real connection.
 *
 * The table is created under the `ww` prefix, exactly as the schema factory would
 * render it, so the suite proves prefix handling in the table-exists check while
 * leaving any live rule table untouched.
 */
#[CoversClass(RuleSeeder::class)]
#[CoversMethod(RuleSeeder::class, '__construct')]
#[CoversMethod(RuleSeeder::class, 'ruleTableExists')]
#[CoversMethod(RuleSeeder::class, 'seed')]
final class RuleSeederIntegrationTest extends TestCase
{
    /** @var list<string> */
    private const array ROUTE_NAMES = ['admin.acl', 'admin.acl.role.read', 'user.session.read'];

    private AdapterInterface $adapter;

    private MetadataInterface $metadata;

    private TableIdentifier $table;

    private RuleSeeder $seeder;

    private bool $ready = false;

    #[Test]
    #[Group('integration')]
    #[Group('integration-mysql')]
    public function findsTheTableUnderItsResolvedPrefix(): void
    {
        $this->createRuleTable();

        self::assertSame('ww_acl_rule', $this->table->getTable());
        self::assertTrue($this->seeder->ruleTableExists());
    }

    #[Test]
    #[Group('integration')]
    #[Group('integration-mysql')]
    public function replacesChangedValuesWithoutChurningRowIds(): void
    {
        $this->createRuleTable();

        $this->seeder->seed(
            ruleSeeds : [
                $this->seed(
                    resourceId: 'admin.acl',
                    roleId    : 'Developer',
                ),
                $this->seed(
                    resourceId: 'user.session.read',
                    roleId    : 'Guest',
                ),
            ],
            routeNames: self::ROUTE_NAMES,
        );

        $before = $this->rows();

        $this->seeder->seed(
            ruleSeeds : [
                $this->seed(
                    resourceId: 'admin.acl',
                    roleId    : 'Developer',
                ),
                $this->seed(
                    type      : RuleType::Deny,
                    resourceId: 'user.session.read',
                    roleId    : 'Guest',
                ),
            ],
            routeNames: self::ROUTE_NAMES,
        );

        $after = $this->rows();

        self::assertCount(2, $after);
        self::assertSame($before[0]['id'], $after[0]['id']);
        self::assertSame($before[1]['id'], $after[1]['id']);
        self::assertSame('Deny', $after[1]['type']);
    }

    #[Test]
    #[Group('integration')]
    #[Group('integration-mysql')]
    public function reportsAMissingTableInsteadOfThrowing(): void
    {
        self::assertFalse($this->seeder->ruleTableExists());

        $result = $this->seeder->seed(
            ruleSeeds : [$this->seed(
                resourceId: 'user.session.read',
                roleId    : 'Guest',
            )],
            routeNames: self::ROUTE_NAMES,
        );

        self::assertTrue($result->tableMissing);
        self::assertSame(0, $result->seeded);
        self::assertSame([], $result->violations);
    }

    #[Test]
    #[Group('integration')]
    #[Group('integration-mysql')]
    public function seedsRowsIncludingAnAnchorAndItsChildren(): void
    {
        $this->createRuleTable();

        $result = $this->seeder->seed(
            ruleSeeds : [
                $this->seed(
                    resourceId: 'admin.acl',
                    roleId    : 'Developer',
                ),
                $this->seed(
                    resourceId      : 'admin.acl.role.read',
                    roleId          : 'Developer',
                    parentResourceId: 'admin.acl',
                    assertions      : ['Ownership'],
                ),
                $this->seed(
                    type      : RuleType::Deny,
                    resourceId: 'user.session.read',
                    roleId    : 'Guest',
                ),
            ],
            routeNames: self::ROUTE_NAMES,
        );

        self::assertSame(3, $result->seeded);
        self::assertFalse($result->tableMissing);
        self::assertSame([], $result->violations);

        $rows = $this->rows();

        self::assertSame(3, $this->countRows(), 'rows: ' . json_encode($rows));
        self::assertCount(3, $rows);
        self::assertSame('Allow', $rows[0]['type']);
        self::assertSame('admin.acl', $rows[0]['resourceId']);
        self::assertNull($rows[0]['parentResourceId']);
        self::assertNull($rows[0]['assertions']);
        self::assertSame('admin.acl', $rows[1]['parentResourceId']);
        self::assertSame('["Ownership"]', $rows[1]['assertions']);
        self::assertSame('Deny', $rows[2]['type']);
        self::assertSame('Guest', $rows[2]['roleId']);
    }

    protected function setUp(): void
    {
        if (! getenv(name: 'TESTS_ADAPTER_MYSQL_HOSTNAME')) {
            self::markTestSkipped('MySQL adapter is not configured.');
        }

        [$this->adapter, $this->metadata, $this->table] = $this->containerServices();

        $this->seeder = new RuleSeeder(
            adapter  : $this->adapter,
            table    : $this->table,
            metadata : $this->metadata,
            validator: new RuleSeedValidator(),
        );

        $this->ready = true;
    }

    protected function tearDown(): void
    {
        if (! $this->ready) {
            return;
        }

        $this->execute(ddl: new DropTable(table: $this->table)->ifExists());
    }

    /**
     * @return array{0: AdapterInterface, 1: MetadataInterface, 2: TableIdentifier}
     */
    private function containerServices(): array
    {
        $container = new ServiceManager();
        $container->configure(new PhpDbConfigProvider()->getDependencies());
        $container->configure(new Mysql\ConfigProvider()->getDependencies());
        $container->configure(new WebwareProvider()()['dependencies']);
        $container->setService('config', [
            AdapterInterface::class => [
                'driver'     => Mysql\Pdo\Driver::class,
                'connection' => [
                    'host'     => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_HOSTNAME'),
                    'port'     => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_PORT'),
                    'username' => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_USERNAME'),
                    'password' => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_PASSWORD'),
                    'dbname'   => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_DATABASE'),
                ],
            ],
            SchemaInterface::class  => [
                'prefix'    => 'ww',
                'separator' => '_',
                'schema'    => (string) getenv(name: 'TESTS_ADAPTER_MYSQL_DATABASE'),
            ],
        ]);

        /** @var SchemaFactory $schemaFactory */
        $schemaFactory = $container->get(SchemaFactory::class);

        /** @var AdapterInterface $adapter */
        $adapter = $container->get(AdapterInterface::class);

        /** @var MetadataInterface $metadata */
        $metadata = $container->get(MetadataInterface::class);

        return [$adapter, $metadata, $schemaFactory(RuleSchema::Rules)];
    }

    private function countRows(): int
    {
        $table = $this->table->getTable();

        foreach ($this->adapter->executeQuery(sql: "SELECT COUNT(*) AS total FROM {$table}") as $row) {
            if (is_array($row)) {
                return (int) $row['total'];
            }
        }

        return -1;
    }

    private function createRuleTable(): void
    {
        $createTable = new CreateTable(table: $this->table);
        $createTable->addColumn(
            new Integer(
                name    : 'id',
                nullable: false,
            )->setOptions(options: ['unsigned' => true, 'autoincrement' => true]),
        );
        $createTable->addColumn(new Varchar(
            name    : 'type',
            length  : 10,
            nullable: false,
        ));
        $createTable->addColumn(new Varchar(
            name    : 'roleId',
            length  : 50,
            nullable: false,
        ));
        $createTable->addColumn(new Varchar(
            name    : 'resourceId',
            length  : 255,
            nullable: false,
        ));
        $createTable->addColumn(new Json(
            name    : 'assertions',
            nullable: true,
            default : null,
        ));
        $createTable->addColumn(new Varchar(
            name    : 'parentResourceId',
            length  : 255,
            nullable: true,
        ));
        $createTable->addConstraint(new PrimaryKey(columns: 'id'));
        $createTable->addConstraint(new UniqueKey(
            columns: ['roleId', 'resourceId'],
            name   : 'uq_rule',
        ));

        $this->execute(ddl: $createTable);
    }

    private function execute(CreateTable|DropTable $ddl): void
    {
        // Rendered through Sql so the driver's DDL decorator produces the column
        // spec, including AUTO_INCREMENT - without it every row after the first
        // collides on the primary key and INSERT IGNORE drops it silently.
        $this->adapter->executeQuery(
            sql: new Sql($this->adapter)->buildSqlString(
                sqlObject: $ddl,
                adapter  : $this->adapter,
            ),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        $rows = [];

        $table = $this->table->getTable();

        foreach ($this->adapter->executeQuery(
            sql: "SELECT id, type, roleId, resourceId, assertions, parentResourceId FROM {$table} ORDER BY id",
        ) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param list<string> $assertions
     */
    private function seed(
        string $resourceId,
        string $roleId,
        RuleType $type = RuleType::Allow,
        ?string $parentResourceId = null,
        array $assertions = [],
    ): RuleSeed {
        return new RuleSeed(
            type            : $type,
            roleId          : $roleId,
            resourceId      : $resourceId,
            assertions      : $assertions,
            parentResourceId: $parentResourceId,
        );
    }
}

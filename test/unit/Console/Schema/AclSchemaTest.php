<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console\Schema;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Sql\Platform;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Console\Ddl\Column\Enum;
use Webware\Acl\Console\Schema\AclSchema;
use Webware\Acl\Container\Configuration;
use Webware\Acl\RouteProvider;
use Webware\Admin\Container\Configuration as AdminConfiguration;

use function array_column;
use function array_keys;

#[CoversClass(AclSchema::class)]
final class AclSchemaTest extends TestCase
{
    #[Test]
    public function dropTablesDropsRuleBeforeRole(): void
    {
        $tables = new AclSchema()->dropTables();

        self::assertCount(2, $tables);
        self::assertContainsOnlyInstancesOf(DropTable::class, $tables);
        self::assertTrue($tables[0]->getIfExists());
        self::assertTrue($tables[1]->getIfExists());
        self::assertStringContainsString('acl_rule', $tables[0]->getSqlString());
        self::assertStringContainsString('acl_role', $tables[1]->getSqlString());
    }

    /**
     * The invariant the seed exists to satisfy: a rule can only grant access to a route
     * that exists, so every seeded resource id must be a name the RouteProvider registers.
     */
    #[Test]
    public function everySeededResourceIdIsARegisteredRouteName(): void
    {
        /** @var list<string|null> $names */
        $names = [];

        $collector = $this->createStub(RouteCollectorInterface::class);

        foreach (['get', 'post', 'patch', 'delete', 'route'] as $method) {
            $collector->method($method)
                ->willReturnCallback(
                    static function (
                        string $path,
                        MiddlewareInterface $middleware,
                        ?string $name = null,
                    ) use (&$names): Route {
                        $names[] = $name;

                        return new Route($path, $middleware, ['GET'], $name);
                    },
                );
        }

        $middlewareFactory = $this->createStub(MiddlewareFactoryInterface::class);
        $middlewareFactory->method('prepare')
            ->willReturn(
                $this->createStub(MiddlewareInterface::class),
            );

        $adminName  = AdminConfiguration::ADMIN_NAME;
        $namePrefix = Configuration::getAdminRouteNamePrefix($adminName);

        new RouteProvider(
            Configuration::getAdminRouteSegment($adminName),
            $namePrefix,
        )->registerRoutes($collector, $middlewareFactory);

        $seeded = array_column(new AclSchema()->ruleSeeds($namePrefix), 'resourceId');

        self::assertEqualsCanonicalizing($names, $seeded);
    }

    #[Test]
    public function roleTableBuildsAclRoleSchema(): void
    {
        $table = new AclSchema()->roleTable();

        self::assertTrue($table->getIfNotExists());

        $columns = $this->columns($table);
        self::assertCount(3, $columns);

        self::assertInstanceOf(Integer::class, $columns[0]);
        self::assertSame('id', $columns[0]->getName());
        self::assertFalse($columns[0]->isNullable());
        self::assertSame(['unsigned' => true, 'autoincrement' => true], $columns[0]->getOptions());

        self::assertInstanceOf(Varchar::class, $columns[1]);
        self::assertSame('roleId', $columns[1]->getName());
        self::assertSame(50, $columns[1]->getLength());
        self::assertFalse($columns[1]->isNullable());

        self::assertInstanceOf(Json::class, $columns[2]);
        self::assertSame('parentId', $columns[2]->getName());
        self::assertTrue($columns[2]->isNullable());
        self::assertNull($columns[2]->getDefault());
        self::assertSame(
            ['comment' => 'Array of parent roleId strings, e.g. ["Guest","Member"]'],
            $columns[2]->getOptions(),
        );

        $constraints = $this->constraints($table);
        self::assertCount(2, $constraints);
        self::assertInstanceOf(PrimaryKey::class, $constraints[0]);
        self::assertSame(['id'], $constraints[0]->getColumns());
        self::assertInstanceOf(UniqueKey::class, $constraints[1]);
        self::assertSame(['roleId'], $constraints[1]->getColumns());
        self::assertSame('uq_role_id', $constraints[1]->getExpressionData()['values'][0]->getValue());

        $this->assertTableOptions($table);
    }

    #[Test]
    public function roleTableRendersValidMysqlDdl(): void
    {
        $sql = $this->renderSql(new AclSchema()->roleTable());

        self::assertStringContainsString('CREATE TABLE', $sql);
        self::assertStringContainsString('IF NOT EXISTS', $sql);
        self::assertStringContainsString('`acl_role`', $sql);
        self::assertStringContainsString('`id`', $sql);
        self::assertStringContainsString('UNSIGNED', $sql);
        self::assertStringContainsString('AUTO_INCREMENT', $sql);
        self::assertStringContainsString('VARCHAR(50)', $sql);
        self::assertStringContainsString('JSON', $sql);
        self::assertStringContainsString('PRIMARY KEY', $sql);
        self::assertStringContainsString('uq_role_id', $sql);
        self::assertStringContainsString('ENGINE = InnoDB', $sql);
        self::assertStringContainsString('utf8mb4_0900_ai_ci', $sql);
    }

    #[Test]
    public function ruleSeedsDefinesAclManagerResourcesForDeveloper(): void
    {
        self::assertSame(
            [
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl',
                    'assertions'       => null,
                    'parentResourceId' => null,
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.role.read',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.role.add.modal',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.role.edit.modal',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.role.create',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.role.update',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.role.delete',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.rule.create',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.rule.update',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.rule.delete',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Developer',
                    'resourceId'       => 'admin.acl.rule.delete.modal',
                    'assertions'       => null,
                    'parentResourceId' => 'admin.acl',
                ],
            ],
            new AclSchema()->ruleSeeds(Configuration::getAdminRouteNamePrefix(AdminConfiguration::ADMIN_NAME)),
        );
    }

    #[Test]
    public function ruleTableBuildsAclRuleSchema(): void
    {
        $table = new AclSchema()->ruleTable();

        self::assertTrue($table->getIfNotExists());

        $columns = $this->columns($table);
        self::assertCount(6, $columns);

        self::assertInstanceOf(Integer::class, $columns[0]);
        self::assertSame('id', $columns[0]->getName());
        self::assertFalse($columns[0]->isNullable());
        self::assertSame(['unsigned' => true, 'autoincrement' => true], $columns[0]->getOptions());

        self::assertInstanceOf(Enum::class, $columns[1]);
        self::assertSame('type', $columns[1]->getName());
        self::assertFalse($columns[1]->isNullable());
        self::assertSame('Allow', $columns[1]->getDefault());
        self::assertSame([], $columns[1]->getOptions());
        self::assertStringContainsString("ENUM('Allow','Deny')", $columns[1]->getExpressionData()['spec']);

        self::assertInstanceOf(Varchar::class, $columns[2]);
        self::assertSame('roleId', $columns[2]->getName());
        self::assertSame(50, $columns[2]->getLength());
        self::assertFalse($columns[2]->isNullable());

        self::assertInstanceOf(Varchar::class, $columns[3]);
        self::assertSame('resourceId', $columns[3]->getName());
        self::assertSame(255, $columns[3]->getLength());
        self::assertFalse($columns[3]->isNullable());
        self::assertSame(
            ['comment' => 'ACL resource string, e.g. "acl.manager.rule"'],
            $columns[3]->getOptions(),
        );

        self::assertInstanceOf(Json::class, $columns[4]);
        self::assertSame('assertions', $columns[4]->getName());
        self::assertTrue($columns[4]->isNullable());
        self::assertNull($columns[4]->getDefault());
        self::assertSame(
            ['comment' => 'Array of assertion alias strings, null means no assertions'],
            $columns[4]->getOptions(),
        );

        self::assertInstanceOf(Varchar::class, $columns[5]);
        self::assertSame('parentResourceId', $columns[5]->getName());
        self::assertSame(255, $columns[5]->getLength());
        self::assertTrue($columns[5]->isNullable());
        self::assertSame(
            ['comment' => 'resourceId of the parent rule; null = explicit rule'],
            $columns[5]->getOptions(),
        );

        $constraints = $this->constraints($table);
        self::assertCount(2, $constraints);
        self::assertInstanceOf(PrimaryKey::class, $constraints[0]);
        self::assertSame(['id'], $constraints[0]->getColumns());
        self::assertInstanceOf(UniqueKey::class, $constraints[1]);
        self::assertSame(['roleId', 'resourceId'], $constraints[1]->getColumns());
        self::assertSame('uq_rule', $constraints[1]->getExpressionData()['values'][0]->getValue());

        $this->assertTableOptions($table);
    }

    #[Test]
    public function ruleTableRendersValidMysqlDdl(): void
    {
        $sql = $this->renderSql(new AclSchema()->ruleTable());

        self::assertStringContainsString('CREATE TABLE', $sql);
        self::assertStringContainsString('IF NOT EXISTS', $sql);
        self::assertStringContainsString('`acl_rule`', $sql);
        self::assertStringContainsString("ENUM('Allow','Deny')", $sql);
        self::assertStringContainsString('VARCHAR(255)', $sql);
        self::assertStringContainsString('PRIMARY KEY', $sql);
        self::assertStringContainsString('uq_rule', $sql);
        self::assertStringContainsString('ENGINE = InnoDB', $sql);
        self::assertStringContainsString('utf8mb4_0900_ai_ci', $sql);
    }

    private function assertTableOptions(CreateTable $table): void
    {
        $options = $table->getOptions();

        self::assertSame(['engine', 'default charset', 'collate'], array_keys($options));
        self::assertInstanceOf(Literal::class, $options['engine']);
        self::assertSame('InnoDB', $options['engine']->getLiteral());
        self::assertSame('utf8mb4', $options['default charset']->getLiteral());
        self::assertSame('utf8mb4_0900_ai_ci', $options['collate']->getLiteral());
    }

    /**
     * @return list<ColumnInterface>
     */
    private function columns(CreateTable $table): array
    {
        /** @var list<ColumnInterface> */
        return $table->getRawState('columns');
    }

    /**
     * @return list<ConstraintInterface>
     */
    private function constraints(CreateTable $table): array
    {
        /** @var list<ConstraintInterface> */
        return $table->getRawState('constraints');
    }

    private function renderSql(CreateTable|DropTable $sql): string
    {
        $driver     = $this->createStub(DriverInterface::class);
        $connection = $this->createStub(ConnectionInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $platform = new Platform();
        $platform->setSubject($sql);

        return $platform->getSqlString(new AdapterPlatform($driver));
    }
}

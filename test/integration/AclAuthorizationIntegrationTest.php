<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl;

use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\RouteCollectorInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;
use PhpDb\Mysql\ConfigProvider as MysqlConfigProvider;
use PhpDb\Mysql\Pdo\Driver;
use PhpDb\ResultSet\ArrayResultSet;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\TableGateway\TableGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webware\Acl\Acl;
use Webware\Acl\Assertion\AssertionAggregateFactory;
use Webware\Acl\AssertionManager;
use Webware\Acl\Console\InitDBCommand;
use Webware\Acl\Entity\Role as RoleEntity;
use Webware\Acl\Query\FetchAclRoleRegistryQuery;
use Webware\Acl\Query\FetchAllRulesQuery;
use Webware\Acl\Query\FetchDistinctResourceIdsQuery;
use Webware\Acl\QueryHandler\FetchAclRoleRegistryHandler;
use Webware\Acl\QueryHandler\FetchAllRulesHandler;
use Webware\Acl\QueryHandler\FetchDistinctResourceIdsHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\Schema;
use Webware\Core\Role;
use Webware\Core\UserInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\Query\QueryResult;
use WebwareTestIntegration\Acl\TestAsset\RouteResource;

use function getenv;
use function json_encode;
use function sprintf;

/**
 * Exercises the seed -> load -> decision seam against a live MySQL database:
 * acl:init-db writes the rows, Acl::load() reads them back through the real
 * query handlers, and isAllowedRoute() answers from what was loaded.
 */
#[CoversClass(Acl::class)]
final class AclAuthorizationIntegrationTest extends TestCase
{
    private const string LOGIN_ROUTE = 'user.manager.session.read';

    #[Test]
    public function baseSeedDrivesARealAuthorizationDecision(): void
    {
        $adapter = $this->createAdapter();
        $this->initDb($adapter);

        $acl = $this->createAcl($adapter);

        // The base seed grants Developer an allow on the ACL manager resource.
        self::assertTrue(
            $acl->isAllowedRoute(
                $this->user(Role::Developer->value),
                new RouteResource('acl.manager', Role::Developer),
            ),
        );

        // No rule exists for Guest anywhere in the base seed.
        self::assertFalse(
            $acl->isAllowedRoute(
                $this->user(Role::Guest->value),
                new RouteResource('acl.manager', Role::Guest),
            ),
        );

        // Fail closed: a resource no rule mentions is denied.
        self::assertFalse(
            $acl->isAllowedRoute(
                $this->user(Role::Developer->value),
                new RouteResource('not.a.resource', Role::Developer),
            ),
        );
    }

    #[Test]
    public function guestAllowWithMemberDenyDiscriminatesOnTheLoginRoute(): void
    {
        $adapter = $this->createAdapter();
        $this->initDb($adapter);
        $this->insertRule($adapter, type: 'Allow', roleId: Role::Guest->value, resourceId: self::LOGIN_ROUTE);
        $this->insertRule($adapter, type: 'Deny', roleId: Role::Member->value, resourceId: self::LOGIN_ROUTE);

        $acl = $this->createAcl($adapter);

        // Member inherits the guest allow, so only its own deny separates them.
        self::assertTrue(
            $acl->isAllowedRoute(
                $this->user(Role::Guest->value),
                new RouteResource(self::LOGIN_ROUTE, Role::Guest),
            ),
        );
        self::assertFalse(
            $acl->isAllowedRoute(
                $this->user(Role::Member->value),
                new RouteResource(self::LOGIN_ROUTE, Role::Member),
            ),
        );
    }

    #[Test]
    public function memberInheritsAGuestAllowThroughTheSeededParentChain(): void
    {
        $adapter = $this->createAdapter();
        $this->initDb($adapter);
        $this->insertRule($adapter, type: 'Allow', roleId: Role::Guest->value, resourceId: self::LOGIN_ROUTE);

        $acl = $this->createAcl($adapter);

        // Member has no rule of its own; this only passes if load() built the
        // Member -> Guest parent chain from acl_role.parentId.
        self::assertTrue(
            $acl->isAllowedRoute(
                $this->user(Role::Member->value),
                new RouteResource(self::LOGIN_ROUTE, Role::Member),
            ),
        );
    }

    #[Test]
    public function seededRolesRoundTripThroughTheJsonColumn(): void
    {
        $adapter = $this->createAdapter();
        $this->initDb($adapter);

        $stored = [];
        foreach ($adapter->executeQuery('SELECT roleId, parentId FROM acl_role ORDER BY id') as $row) {
            $stored[] = ['roleId' => $row['roleId'], 'parentId' => $row['parentId']];
        }

        $expected = [];
        foreach (Role::getRoles() as $seed) {
            $expected[] = [
                'roleId'   => $seed['roleId'],
                'parentId' => json_encode($seed['parentIds']),
            ];
        }

        self::assertSame($expected, $stored);
    }

    private function createAcl(AdapterInterface $adapter): Acl
    {
        $rulesGateway = $this->gateway(Schema::Rules->value, $adapter, new ArrayResultSet());
        $rolesGateway = $this->gateway(
            Schema::Roles->value,
            $adapter,
            new RowPrototypeResultSet(rowPrototype: new RoleEntity()),
        );

        $allRules  = new FetchAllRulesHandler($rulesGateway);
        $resources = new FetchDistinctResourceIdsHandler($rulesGateway);
        $registry  = new FetchAclRoleRegistryHandler(new RoleRepository($rolesGateway));

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static fn(MessageInterface $message): QueryResult => match (true) {
                $message instanceof FetchAllRulesQuery => $allRules->handle($message),
                $message instanceof FetchDistinctResourceIdsQuery => $resources->handle($message),
                $message instanceof FetchAclRoleRegistryQuery => $registry->handle($message),
                default => throw new RuntimeException('Unmapped query: ' . $message::class),
            },
        );

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')->willReturn([]);

        return new Acl(
            $bus,
            new AssertionAggregateFactory(new AssertionManager(new ServiceManager())),
            $routeCollector,
        );
    }

    private function createAdapter(): AdapterInterface
    {
        $config = [
            AdapterInterface::class => [
                'driver'     => Driver::class,
                'connection' => [
                    'dbname'   => getenv('TESTS_ADAPTER_MYSQL_DATABASE'),
                    'host'     => getenv('TESTS_ADAPTER_MYSQL_HOSTNAME'),
                    'port'     => getenv('TESTS_ADAPTER_MYSQL_PORT'),
                    'username' => getenv('TESTS_ADAPTER_MYSQL_USERNAME'),
                    'password' => getenv('TESTS_ADAPTER_MYSQL_PASSWORD'),
                ],
            ],
        ];

        $container = new ServiceManager();
        $container->configure(new PhpDbConfigProvider()->getDependencies());
        $container->configure(new MysqlConfigProvider()->getDependencies());
        $container->setService('config', $config);

        return $container->get(AdapterInterface::class);
    }

    private function gateway(
        string $table,
        AdapterInterface $adapter,
        ArrayResultSet|RowPrototypeResultSet $prototype,
    ): TableGateway {
        return new TableGateway(
            table             : $table,
            adapter           : $adapter,
            resultSetPrototype: $prototype,
        );
    }

    private function initDb(AdapterInterface $adapter): void
    {
        $tester = new CommandTester(new InitDBCommand($adapter));

        self::assertSame(Command::SUCCESS, $tester->execute(['--drop' => true]));
    }

    private function insertRule(
        AdapterInterface $adapter,
        string $type,
        string $roleId,
        string $resourceId,
    ): void {
        $adapter->query(
            sprintf(
                'INSERT INTO acl_rule (type, roleId, resourceId, assertions, parentResourceId)'
                    . " VALUES ('%s', '%s', '%s', NULL, NULL)",
                $type,
                $roleId,
                $resourceId,
            ),
            AdapterInterface::QUERY_MODE_EXECUTE,
        );
    }

    private function user(string $roleId): UserInterface
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoleId')->willReturn($roleId);

        return $user;
    }
}

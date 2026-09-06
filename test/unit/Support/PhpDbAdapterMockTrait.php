<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Support;

use LogicException;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\ResultSet\ArrayResultSet;
use PhpDb\ResultSet\RowPrototypeResultSet;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;
use PhpDb\Sql\TableIdentifier;
use PhpDb\TableGateway\TableGateway;
use Throwable;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Admin\CommandHandler\SaveRoleHandler;
use Webware\Acl\Entity\Role;
use Webware\Acl\Entity\Rule;
use Webware\Acl\Query\FetchAclRoleRegistry;
use Webware\Acl\Query\FetchAllRoles;
use Webware\Acl\Query\FetchAllRules;
use Webware\Acl\Query\FetchDistinctResourceIds;
use Webware\Acl\QueryHandler\FetchAclRoleRegistryHandler;
use Webware\Acl\QueryHandler\FetchAllRolesHandler;
use Webware\Acl\QueryHandler\FetchAllRulesHandler;
use Webware\Acl\QueryHandler\FetchDistinctResourceIdsHandler;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\RuleRepository;
use Webware\Acl\Repository\Schema;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\ResultInterface as MessageBusResultInterface;

use function array_fill;
use function count;
use function sprintf;

trait PhpDbAdapterMockTrait
{
    /** @var list<PreparableSqlInterface> */
    protected array $preparedSqlObjects = [];

    /**
     * @param list<list<mixed>> $statementResults
     * @param array<int, int> $affectedRows
     */
    protected function createAdapter(
        array $statementResults,
        array $affectedRows = [],
        ?Throwable $statementException = null,
        int|string|null $lastGeneratedValue = 7,
    ): AdapterInterface {
        $this->preparedSqlObjects = [];

        $adapter    = $this->createStub(AdapterInterface::class);
        $driver     = $this->createStub(DriverInterface::class);
        $connection = $this->createStub(ConnectionInterface::class);
        $platform   = $this->createStub(PlatformInterface::class);
        $decorator  = $this->createStubForIntersectionOfInterfaces([
            PlatformDecoratorInterface::class,
            PreparableSqlInterface::class,
        ]);

        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn($platform);
        $driver->method('getConnection')->willReturn($connection);
        $connection->method('getLastGeneratedValue')->willReturn($lastGeneratedValue);
        $platform->method('getSqlPlatformDecorator')->willReturn($decorator);
        $decorator->method('setSubject')
            ->willReturnCallback(
                function (SqlInterface|PreparableSqlInterface|null $subject) use (
                    $decorator,
                ): PlatformDecoratorInterface {
                    if ($subject instanceof PreparableSqlInterface) {
                        $this->preparedSqlObjects[] = $subject;
                    }

                    return $decorator;
                },
            );
        $decorator->method('prepareStatement')->willReturnArgument(1);

        if (null !== $statementException) {
            $driver->method('createStatement')->willThrowException($statementException);

            return $adapter;
        }

        $statements = [];
        foreach ($statementResults as $index => $rows) {
            $statements[] = $this->createStatement($rows, $affectedRows[$index] ?? 1);
        }
        if ([] !== $statements) {
            $driver->method('createStatement')->willReturnOnConsecutiveCalls(...$statements);
        }

        return $adapter;
    }

    /**
     * Builds a message-bus fake that dispatches the read/write queries to the
     * real handlers backed by the mocked adapter. Reuses the existing adapter
     * fixture machinery so per-statement result queues stay identical.
     */
    protected function createQueryBus(AdapterInterface $adapter): MessageBusInterface
    {
        $ruleGateway     = $this->createRuleArrayGateway($adapter);
        $roleRepository  = $this->createRoleRepository($adapter);
        $saveRoleHandler = new SaveRoleHandler($roleRepository);

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('handle')->willReturnCallback(
            static function (MessageInterface $message) use (
                $ruleGateway,
                $roleRepository,
                $saveRoleHandler,
            ): MessageBusResultInterface {
                if ($message instanceof FetchAllRules) {
                    return new FetchAllRulesHandler($ruleGateway)->handle($message);
                }
                if ($message instanceof FetchDistinctResourceIds) {
                    return new FetchDistinctResourceIdsHandler($ruleGateway)->handle($message);
                }
                if ($message instanceof FetchAclRoleRegistry) {
                    return new FetchAclRoleRegistryHandler($roleRepository)->handle($message);
                }
                if ($message instanceof FetchAllRoles) {
                    return new FetchAllRolesHandler($roleRepository)->handle($message);
                }
                if ($message instanceof SaveRoleCommand) {
                    return $saveRoleHandler->handle($message);
                }

                throw new LogicException(sprintf('Unexpected message: %s', $message::class));
            },
        );

        return $bus;
    }

    protected function createRoleGateway(AdapterInterface $adapter): TableGateway
    {
        return new TableGateway(
            table             : new TableIdentifier(Schema::Roles->value),
            adapter           : $adapter,
            resultSetPrototype: new RowPrototypeResultSet(
                rowPrototype: new Role(),
            ),
        );
    }

    protected function createRoleRepository(AdapterInterface $adapter): RoleRepository
    {
        return new RoleRepository($this->createRoleGateway($adapter));
    }

    protected function createRuleArrayGateway(AdapterInterface $adapter): TableGateway
    {
        return new TableGateway(
            table             : new TableIdentifier(Schema::Rules->value),
            adapter           : $adapter,
            resultSetPrototype: new ArrayResultSet(),
        );
    }

    protected function createRuleGateway(AdapterInterface $adapter): TableGateway
    {
        return new TableGateway(
            table             : new TableIdentifier(Schema::Rules->value),
            adapter           : $adapter,
            resultSetPrototype: new RowPrototypeResultSet(
                rowPrototype: new Rule(),
            ),
        );
    }

    protected function createRuleRepository(AdapterInterface $adapter): RuleRepository
    {
        return new RuleRepository($this->createRuleGateway($adapter));
    }

    /**
     * @param list<mixed> $rows
     */
    private function createResult(array $rows, int $affectedRows): ResultInterface
    {
        $result = $this->createStub(ResultInterface::class);
        $result->method('getFieldCount')->willReturn(0);
        $result->method('isBuffered')->willReturn(false);
        $result->method('getAffectedRows')->willReturn($affectedRows);
        $result->method('valid')
            ->willReturnOnConsecutiveCalls(
                ...[...array_fill(0, count($rows), value: true), false],
            );
        if ([] !== $rows) {
            $result->method('current')->willReturnOnConsecutiveCalls(...$rows);
        }

        return $result;
    }

    /**
     * @param list<mixed> $rows
     */
    private function createStatement(array $rows, int $affectedRows): StatementInterface
    {
        $statement = $this->createStub(StatementInterface::class);
        $statement->method('execute')->willReturn($this->createResult($rows, $affectedRows));

        return $statement;
    }
}

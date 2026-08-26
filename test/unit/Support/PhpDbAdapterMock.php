<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Support;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;
use Throwable;

use function array_fill;
use function array_merge;
use function count;

trait PhpDbAdapterMock
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
        } else {
            $statements = [];
            foreach ($statementResults as $index => $rows) {
                $statements[] = $this->createStatement($rows, $affectedRows[$index] ?? 1);
            }
            if ([] !== $statements) {
                $driver->method('createStatement')->willReturnOnConsecutiveCalls(...$statements);
            }
        }

        return $adapter;
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
                ...array_merge(array_fill(0, count($rows), true), [false]),
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

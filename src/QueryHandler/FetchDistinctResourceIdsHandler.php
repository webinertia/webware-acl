<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler;

use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use PhpDb\Sql\Select;
use PhpDb\TableGateway\TableGateway;
use Webware\Acl\Query\FetchDistinctResourceIds;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;

final readonly class FetchDistinctResourceIdsHandler implements QueryHandlerInterface
{
    public function __construct(
        private TableGateway $gateway,
    ) {}

    /**
     * @throws SqlException
     */
    public function handle(FetchDistinctResourceIds $query): QueryResult
    {
        $select = $this->gateway->getSql()->select();
        $select->columns(['resourceId'])->quantifier(Select::QUANTIFIER_DISTINCT);
        $resultSet = $this->gateway->selectWith($select);

        $ids = [];
        /** @var array{resourceId: string} $row */
        foreach ($resultSet as $row) {
            $ids[] = $row['resourceId'];
        }

        return new QueryResult($query, MessageStatus::Success, $ids);
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler;

use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use Webware\Acl\Query\FetchDistinctResourceIds;
use Webware\Acl\Repository\RuleRepository;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;

final readonly class FetchDistinctResourceIdsHandler implements QueryHandlerInterface
{
    public function __construct(
        private RuleRepository $ruleRepository,
    ) {}

    /**
     * @throws SqlException
     */
    public function handle(FetchDistinctResourceIds $query): QueryResult
    {
        return new QueryResult($query, MessageStatus::Success, $this->ruleRepository->fetchDistinctResourceIds());
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler;

use Webware\Acl\Query\FetchAllRules;
use Webware\Acl\Repository\RuleRepository;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;

final readonly class FetchAllRulesHandler implements QueryHandlerInterface
{
    public function __construct(
        private RuleRepository $ruleRepository,
    ) {}

    public function handle(FetchAllRules $query): QueryResult
    {
        return new QueryResult($query, MessageStatus::Success, $this->ruleRepository->fetchAll());
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler;

use PhpDb\TableGateway\TableGateway;
use Webware\Acl\Query\FetchAllRules;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;

use function json_decode;

final readonly class FetchAllRulesHandler implements QueryHandlerInterface
{
    private const array COLUMNS = [
        'type',
        'roleId',
        'resourceId',
        'assertions',
        'parentResourceId',
    ];

    public function __construct(
        private TableGateway $gateway,
    ) {}

    public function handle(FetchAllRules $query): QueryResult
    {
        $rules  = [];
        $select = $this->gateway->getSql()->select();
        $select->columns(self::COLUMNS);
        $resultSet = $this->gateway->selectWith($select);

        /** @var array{type: string, roleId: string, resourceId: string, assertions: string|null, parentResourceId: string|null} $row */
        foreach ($resultSet as $row) {
            $rules[] = [
                'type'             => $row['type'],
                'roleId'           => $row['roleId'],
                'resourceId'       => $row['resourceId'],
                'assertions'       => null === $row['assertions']
                    ? null
                    : json_decode($row['assertions'], associative: true),
                'parentResourceId' => $row['parentResourceId'] ?? null,
            ];
        }
        // we will create a replacement for the RuleRepository using the TableGateway
        return new QueryResult($query, MessageStatus::Success, $rules);
    }
}

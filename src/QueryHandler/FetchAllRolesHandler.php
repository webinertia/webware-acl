<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler;

use Webware\Acl\Query\FetchAllRoles;
use Webware\Acl\Repository\RoleRepository;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;

final readonly class FetchAllRolesHandler implements QueryHandlerInterface
{
    public function __construct(
        private RoleRepository $roleRepository,
    ) {}

    public function handle(FetchAllRoles $query): QueryResult
    {
        return new QueryResult($query, MessageStatus::Success, $this->roleRepository->fetchAll());
    }
}

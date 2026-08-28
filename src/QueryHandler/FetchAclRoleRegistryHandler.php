<?php

declare(strict_types=1);

namespace Webware\Acl\QueryHandler;

use Laminas\Permissions\Acl\Exception\ExceptionInterface as AclException;
use Webware\Acl\Query\FetchAclRoleRegistry;
use Webware\Acl\Repository\RoleRepository;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;

final readonly class FetchAclRoleRegistryHandler implements QueryHandlerInterface
{
    public function __construct(
        private RoleRepository $roleRepository,
    ) {}

    /**
     * @throws AclException
     */
    public function handle(FetchAclRoleRegistry $query): QueryResult
    {
        return new QueryResult($query, MessageStatus::Success, $this->roleRepository->fetchAclRoleRegistry());
    }
}

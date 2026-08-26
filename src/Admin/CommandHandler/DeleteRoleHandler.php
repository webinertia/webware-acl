<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Admin\CommandHandler;

use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use Throwable;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Repository\RoleRepository;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final class DeleteRoleHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
    ) {}

    /**
     * @throws SqlException
     */
    public function handle(DeleteRoleCommand $command): CommandResultInterface
    {
        try {
            $this->roleRepository->removeFromParents($command->roleId);
            $this->roleRepository->delete($command->roleId);
        } catch (Throwable $e) {
            return new CommandResult($command, MessageStatus::Failure, $e);
        }

        return new CommandResult($command, MessageStatus::Success, null);
    }
}

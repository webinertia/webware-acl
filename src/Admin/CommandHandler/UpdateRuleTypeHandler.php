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

use Override;
use Throwable;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\RuleRepository;

use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final class UpdateRuleTypeHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RuleRepository $ruleRepository,
        private readonly RoleRepository $roleRepository,
    ) {}

    #[Override]
    public function handle(UpdateRuleTypeCommand $command): CommandResultInterface
    {
        try {
            $updated = $this->ruleRepository->updateType($command->roleId, $command->resourceId, $command->type);

            if (! $updated) {
                return new CommandResult($command, MessageStatus::Failure, null);
            }

            // Cascade: children with no explicit rule inherit the parent rule type.
            // Add an explicit old-type rule for each such child so they keep their access.
            foreach ($this->roleRepository->fetchDirectChildren($command->roleId) as $childRole) {
                if ($this->ruleRepository->findByRoleAndResource($childRole, $command->resourceId) !== null) { continue; }

$this->ruleRepository->save($command->type, $childRole, $command->resourceId, []);
            }
        } catch (Throwable $e) {
            return new CommandResult($command, MessageStatus::Failure, $e);
        }

        return new CommandResult($command, MessageStatus::Success, null);
    }
}

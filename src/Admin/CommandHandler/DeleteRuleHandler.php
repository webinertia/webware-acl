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
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Repository\RuleRepository;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final class DeleteRuleHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {}

    #[Override]
    public function handle(DeleteRuleCommand $command): CommandResultInterface
    {
        try {
            $deleted = $this->ruleRepository->delete($command->roleId, $command->resourceId);
        } catch (Throwable $e) {
            return new CommandResult($command, MessageStatus::Failure, $e);
        }

        return new CommandResult($command, $deleted ? MessageStatus::Success : MessageStatus::Failure, null);
    }
}

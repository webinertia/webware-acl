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

namespace Webware\Acl\MessageBus;

use Override;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResultInterface;

final readonly class CommandResult implements CommandResultInterface
{
    public function __construct(
        private CommandInterface $command,
        private CommandStatus $status,
        private mixed $result,
    ) {}

    #[Override]
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    #[Override]
    public function getResult(): mixed
    {
        return $this->result;
    }

    #[Override]
    public function getStatus(): CommandStatus
    {
        return $this->status;
    }
}

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

namespace Webware\Acl\Admin\Command;

use Webware\Message\NotificationCapableInterface;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

final readonly class DeleteRoleCommand implements NamedCommandInterface, NotificationCapableInterface
{
    use NamedCommandTrait;

    public string $successMessage;
    public string $failureMessage;

    public function __construct(
        public string $roleId,
    ) {
        $this->successMessage = 'Role deleted.';
        $this->failureMessage = 'Role could not be deleted. Please try again.';
    }
}

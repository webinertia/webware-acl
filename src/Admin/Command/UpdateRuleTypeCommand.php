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

use Webware\Acl\RuleType;
use Webware\Message\NotificationCapableInterface;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

final readonly class UpdateRuleTypeCommand implements NamedCommandInterface, NotificationCapableInterface
{
    use NamedCommandTrait;

    public string $successMessage;
    public string $failureMessage;

    public function __construct(
        public string $roleId,
        public string $resourceId,
        public RuleType $type,
    ) {
        $this->successMessage = 'Rule updated.';
        $this->failureMessage = 'Rule update failed. Please try again.';
    }
}

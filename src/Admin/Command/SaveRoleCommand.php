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

final class SaveRoleCommand implements NamedCommandInterface, NotificationCapableInterface
{
    use NamedCommandTrait;

    public readonly string $successMessage;
    public readonly string $failureMessage;

    public function __construct(
        /**
         * The unique identifier of the role.
         *
         * @var int|null
         */
        public readonly ?int $id,
        /**
         * The role identifier.
         *
         * @var string
         */
        public readonly string $roleId,
        /**
         * The parent role identifiers.
         *
         * @var string[]|null
         */
        public readonly ?array $parentId = null,
    ) {
        $this->successMessage = 'Role saved.';
        $this->failureMessage = 'Role could not be saved. Please try again.';
    }
}

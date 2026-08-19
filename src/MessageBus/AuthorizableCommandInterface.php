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

use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Webware\Acl\PrivilegeInterface;
use Webware\Acl\RoleProviderInterface;
use Webware\MessageBus\Command\CommandInterface;

interface AuthorizableCommandInterface extends
    CommandInterface,
    RoleProviderInterface,
    ResourceInterface,
    PrivilegeInterface {}

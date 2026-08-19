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

namespace Webware\Acl\MessageBus\Middleware;

use Override;
use Webware\Acl\AclInterface;
use Webware\Acl\MessageBus\AuthorizableCommandInterface;
use Webware\Acl\MessageBus\CommandResult;
use Webware\Acl\MessageBus\CommandStatus;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

/**
 * ACL-enforcing middleware for the MessageBus pipeline.
 *
 * Messages that implement AuthorizableCommandInterface are checked via
 * $acl->isAllowed() before the handler middleware is reached. A denied
 * message returns a CommandResult with CommandStatus::Forbidden — no
 * exception is thrown.
 *
 * Messages that do not implement AuthorizableCommandInterface are passed
 * through to the next middleware unchanged.
 */
final readonly class MessageHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AclInterface $acl,
    ) {}

    #[Override]
    public function process(
        MessageInterface $message,
        PipelineHandlerInterface $next,
    ): ResultInterface {
        if (
            $message instanceof AuthorizableCommandInterface
                && ! $this->acl->isAllowed(
                    $message->getRole(),
                    $message,
                    $message->getPrivilegeId(),
                )
        ) {
            return new CommandResult($message, CommandStatus::Forbidden, null);
        }

        return $next->handle($message);
    }
}

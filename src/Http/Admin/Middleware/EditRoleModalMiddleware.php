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

namespace Webware\Acl\Http\Admin\Middleware;

use Laminas\Diactoros\Exception\ExceptionInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Entity\Role;
use Webware\Acl\Query\FetchAllRolesQuery;
use Webware\MessageBus\MessageBusInterface;

use function array_find;

/**
 * Assembles the edit-role modal view model and attaches it to the request as an
 * attribute before passing control to EditRoleModalHandler, which renders it.
 *
 * Attribute key: EditRoleModalMiddleware::class
 *
 * View model shape:
 *   role   ?\Webware\Acl\Entity\Role   the role named by the `roleId` request attribute, or null
 *   roles  list<\Webware\Acl\Entity\Role>
 */
final readonly class EditRoleModalMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string $roleId */
        $roleId = $request->getAttribute('roleId', '');
        /** @var Role[] $roles */
        $roles = $this->messageBus->handle(new FetchAllRolesQuery())->getResult();

        // Find the role being edited so the handler can pre-populate the form
        $role = array_find($roles, static fn(Role $r): bool => $r->getRoleId() === $roleId);

        return $handler->handle($request->withAttribute(self::class, [
            'role'  => $role,
            'roles' => $roles,
        ]));
    }
}

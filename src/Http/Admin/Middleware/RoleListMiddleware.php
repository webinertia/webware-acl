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
use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Entity\Role;
use Webware\Acl\Query\FetchAllRolesQuery;
use Webware\MessageBus\MessageBusInterface;

/**
 * Assembles the role list view model and attaches it to the request as an
 * attribute before passing control to RoleListHandler, which renders it.
 *
 * Attribute key: RoleListMiddleware::class
 *
 * View model shape:
 *   roles              list<\Webware\Acl\Entity\Role>
 *   rolesWithChildren  array<string, true>  roleId → true when the role is a parent of another
 */
final readonly class RoleListMiddleware implements MiddlewareInterface
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
        /** @var Role[] $roles */
        $roles = $this->messageBus->handle(new FetchAllRolesQuery())->getResult();

        // Build a set of roleIds that appear as a parent in any role's parentId.
        // Used by the template to disable the delete button for roles that have children.
        $rolesWithChildren = [];
        foreach ($roles as $role) {
            /**
             * Role::parentId declares `array|string|null`, but its set hook has already
             * decoded any JSON string and mapped it to objects, so the read type is the array.
             *
             * @var array<array-key, RoleInterface> $parents
             */
            $parents = $role->parentId ?? [];

            foreach ($parents as $parent) {
                $rolesWithChildren[$parent->getRoleId()] = true;
            }
        }

        return $handler->handle($request->withAttribute(self::class, [
            'roles'             => $roles,
            'rolesWithChildren' => $rolesWithChildren,
        ]));
    }
}

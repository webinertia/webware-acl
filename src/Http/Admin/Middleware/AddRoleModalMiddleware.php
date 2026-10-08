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

/**
 * Assembles the add-role modal view model and attaches it to the request as an
 * attribute before passing control to AddRoleModalHandler, which renders it.
 *
 * Attribute key: AddRoleModalMiddleware::class
 *
 * View model shape:
 *   roles  list<\Webware\Acl\Entity\Role>
 */
final readonly class AddRoleModalMiddleware implements MiddlewareInterface
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

        return $handler->handle($request->withAttribute(self::class, ['roles' => $roles]));
    }
}

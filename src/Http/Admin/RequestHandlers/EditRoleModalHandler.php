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

namespace Webware\Acl\Http\Admin\RequestHandlers;

use Laminas\Diactoros\Exception\ExceptionInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Entity\Role;
use Webware\Acl\Query\FetchAllRoles;
use Webware\MessageBus\MessageBusInterface;

use function array_find;

/**
 * Returns an HTML fragment containing the edit-role modal content.
 *
 * Intended for HTMX GET requests only. The response is swapped into
 * #sharedModalDialog, then the caller shows #sharedModal.
 */
final class EditRoleModalHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string $roleId */
        $roleId = $request->getAttribute('roleId', '');
        /** @var Role[] $roles */
        $roles = $this->messageBus->handle(new FetchAllRoles())->getResult();

        // Find the role being edited so we can pre-populate the form
        $role = array_find($roles, static fn(Role $r): bool => $r->getRoleId() === $roleId);

        return new HtmlResponse($this->template->render('acl::partials/edit-role-modal', [
            'role'   => $role,
            'roles'  => $roles,
            'layout' => false,
            'body'   => false,
        ]));
    }
}

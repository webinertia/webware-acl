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

namespace Webware\Acl\Http\Admin\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Entity\Role;
use Webware\Acl\Http\Admin\Middleware\EditRoleModalMiddleware;

/**
 * Renders the edit-role modal from the view model EditRoleModalMiddleware
 * attached.
 *
 * Render-only: the roles and the role being edited are assembled by the
 * middleware that runs ahead of this handler in the pipeline.
 *
 * Intended for HTMX GET requests only. The response is swapped into
 * #sharedModalDialog, then the caller shows #sharedModal.
 */
final class EditRoleModalHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{role: ?Role, roles: Role[]} $viewModel */
        $viewModel = $request->getAttribute(EditRoleModalMiddleware::class, ['role' => null, 'roles' => []]);

        return new HtmlResponse($this->template->render('acl::partials/edit-role-modal', [
            'role'   => $viewModel['role'],
            'roles'  => $viewModel['roles'],
            'layout' => false,
            'body'   => false,
        ]));
    }
}

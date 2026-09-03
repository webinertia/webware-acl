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
use Webware\Acl\Query\FetchAllRoles;
use Webware\MessageBus\MessageBusInterface;

/**
 * Returns an HTML fragment containing the add-role modal content.
 *
 * Intended for HTMX GET requests only. The response is swapped into
 * #sharedModalDialog, then the caller shows #sharedModal.
 */
final class AddRoleModalHandler implements RequestHandlerInterface
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
        /** @var Role[] $roles */
        $roles = $this->messageBus->handle(new FetchAllRoles())->getResult();

        return new HtmlResponse($this->template->render('acl::partials/add-role-modal', [
            'roles'  => $roles,
            'layout' => false,
            'body'   => false,
        ]));
    }
}

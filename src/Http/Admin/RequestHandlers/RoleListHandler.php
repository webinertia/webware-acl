<?php

declare(strict_types=1);

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
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

use function json_encode;

final class RoleListHandler implements RequestHandlerInterface
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

        // Build a set of roleIds that appear as a parent in any role's parentId.
        // Used by the template to disable the delete button for roles that have children.
        $rolesWithChildren = [];
        foreach ($roles as $role) {
            foreach ($role->parentId ?? [] as $parent) {
                $rolesWithChildren[$parent->getRoleId()] = true;
            }
        }

        $response = new HtmlResponse($this->template->render('acl::admin-roles', [
            'roles'             => $roles,
            'rolesWithChildren' => $rolesWithChildren,
        ]));

        /** @var CommandResultInterface|null $commandResult */
        $commandResult = $request->getAttribute(CommandResult::class);
        if (
            $commandResult instanceof CommandResultInterface
                && $commandResult->getStatus() === MessageStatus::Success
        ) {
            $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
        }

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Admin\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Entity\Role;
use Webware\Acl\Http\Admin\Middleware\RoleListMiddleware;
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;

use function json_encode;

/**
 * Renders the role list from the view model RoleListMiddleware attached.
 *
 * Render-only: the roles and the parent map are assembled by the middleware
 * that runs ahead of this handler in the pipeline.
 */
final class RoleListHandler implements RequestHandlerInterface
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
        /** @var array{roles: Role[], rolesWithChildren: array<string, true>} $viewModel */
        $viewModel = $request->getAttribute(RoleListMiddleware::class, ['roles' => [], 'rolesWithChildren' => []]);

        $response = new HtmlResponse($this->template->render('acl::admin-roles', $viewModel));

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

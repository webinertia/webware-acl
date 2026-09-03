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
use Webware\Htmx\Response\Header;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;

use function json_encode;

/**
 * Handles GET /admin/access/resources — list all resources with their privileges.
 * Handles POST /admin/access/resources — create a new resource.
 *
 * Route protection for unprotected routes is handled by the Access Control page
 * (admin.acl.read). This handler focuses on low-level resource CRUD only.
 */
final class ResourceListHandler implements RequestHandlerInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly TemplateRendererInterface $template,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $resources */
        $resources  = $this->config['resources'] ?? [];
        $privileges = [];
        $roles      = [];

        $response = new HtmlResponse($this->template->render('acl::admin-resources', [
            'resources'  => $resources,
            'privileges' => $privileges,
            'roles'      => $roles,
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

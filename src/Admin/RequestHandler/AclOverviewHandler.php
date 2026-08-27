<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\RequestHandler;

use Laminas\Diactoros\Exception\ExceptionInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Http\Admin\Middleware\OverviewMiddleware;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;

/**
 * Handles GET /admin/access — route-centric Access Control page.
 *
 * All data assembly is performed by BuildAccessControlMiddleware which runs
 * before this handler in the pipeline and attaches the view model as a request
 * attribute. This handler is render-only.
 */
final class AclOverviewHandler implements RequestHandlerInterface
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
        /** @var array<string, mixed> $viewModel */
        $viewModel = $request->getAttribute(OverviewMiddleware::class, []);

        $response = new HtmlResponse($this->template->render('acl::admin-acl', $viewModel));

        // Close the wizard modal after a successful POST
        /** @var CommandResultInterface|null $result */
        $result = $request->getAttribute(CommandResult::class);
        if ($result instanceof CommandResultInterface && $result->getStatus() === MessageStatus::Success) {
            $response = $response->withHeader('HX-Trigger', 'closeModal');
        }

        return $response;
    }
}

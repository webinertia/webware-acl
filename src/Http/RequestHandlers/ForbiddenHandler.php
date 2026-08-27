<?php

declare(strict_types=1);

namespace Webware\Acl\Http\RequestHandlers;

use Laminas\Diactoros\Response\RedirectResponse;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Core\UserInterface;
use Webware\Message\SystemMessengerInterface;

final readonly class ForbiddenHandler implements RequestHandlerInterface, ForbiddenHandlerInterface
{
    public function __construct(
        private string $loginPath = '/login',
        private ?string $forbiddenRedirect = '/',
        private ?string $forbiddenTemplate = null,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserInterface $user */
        $user = $request->getAttribute(UserInterface::class);

        // Guest identity — silent redirect to login, no toast
        if (null === $user->getIdentity()) {
            return new RedirectResponse($this->loginPath);
        }

        // Authenticated but denied — conditional toast then redirect
        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messenger?->warning(
            'You do not have permission to access the requested resource.',
            hops: 1,
            now: false,
        );

        $serverParams = $request->getServerParams();
        /** @var string $redirect */
        $redirect =
            $this->forbiddenRedirect
                ?? $serverParams['HTTP_REFERER']
                    ?? '/';

        return new RedirectResponse($redirect);
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\Http\Middleware;

use Mezzio\Router\RouteResult;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Webware\Acl\Http\RouteResourceFactoryInterface;
use Webware\Acl\RequestHandler\ForbiddenHandlerInterface;
use Webware\Core\AclInterface;
use Webware\Core\UserInterface;

final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ForbiddenHandlerInterface $forbiddenHandler,
        private readonly RouteResourceFactoryInterface $routeResourceFactory,
    ) {}

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);

        if (null === $routeResult || $routeResult->isFailure()) {
            return $handler->handle($request);
        }

        /** @var AclInterface|null $acl */
        $acl = $request->getAttribute(AclInterface::class);

        if (null === $acl) {
            throw new RuntimeException('AclMiddleware must be in the pipeline before AuthorizationMiddleware.');
        }

        /** @var UserInterface|null $user */
        $user          = $request->getAttribute(UserInterface::class);
        $routeResource = ($this->routeResourceFactory)($routeResult, $request);

        if (! $acl->isAllowedRoute($user, $routeResource)) {
            return $this->forbiddenHandler->handle($request);
        }

        return $handler->handle($request);
    }
}

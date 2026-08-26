<?php

declare(strict_types=1);

namespace Webware\Acl\Http;

use Mezzio\Router\RouteResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

final class RouteResourceFactory implements RouteResourceFactoryInterface
{
    /**
     * @param array<string, array<string, mixed>> $paramMap
     */
    public function __construct(
        private readonly array $paramMap = [],
    ) {}

    #[Override]
    public function __invoke(
        RouteResult $routeResult,
        ServerRequestInterface $request,
    ): RouteResourceInterface {
        return new RouteResource($routeResult, $request, $this->paramMap);
    }
}

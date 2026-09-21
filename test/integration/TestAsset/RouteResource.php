<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl\TestAsset;

use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;
use Webware\Acl\Http\RouteResourceInterface;

/**
 * A route resource with a fixed id, so a test can name the resource under test
 * without constructing a Mezzio RouteResult.
 */
final readonly class RouteResource implements RouteResourceInterface
{
    public function __construct(
        private string $resourceId,
        private RoleInterface $role,
    ) {}

    #[Override]
    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    #[Override]
    public function getRole(): RoleInterface
    {
        return $this->role;
    }
}

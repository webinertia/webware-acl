<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Acl;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Acl\RuleSeeds;
use Webware\Acl\Container\Configuration;
use Webware\Acl\RouteProvider;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleType;
use Webware\Core\Role;

use function array_map;
use function array_slice;

#[CoversClass(RuleSeeds::class)]
#[CoversMethod(RuleSeeds::class, 'ruleSeeds')]
final class RuleSeedsTest extends TestCase
{
    /**
     * A rule can only grant access to a route that exists, so every seeded
     * resource id must be a name the RouteProvider registers.
     */
    #[Test]
    public function everySeededResourceIdIsARegisteredRouteName(): void
    {
        /** @var list<string|null> $names */
        $names = [];

        $collector = $this->createStub(RouteCollectorInterface::class);

        foreach (['get', 'post', 'patch', 'delete', 'route'] as $method) {
            $collector->method($method)
                ->willReturnCallback(
                    static function (
                        string $path,
                        MiddlewareInterface $middleware,
                        ?string $name = null,
                    ) use (&$names): Route {
                        $names[] = $name;

                        return new Route($path, $middleware, ['GET'], $name);
                    },
                );
        }

        $middlewareFactory = $this->createStub(MiddlewareFactoryInterface::class);
        $middlewareFactory->method('prepare')->willReturn($this->createStub(MiddlewareInterface::class));

        new RouteProvider(
            Configuration::getAdminRouteSegment('admin'),
            Configuration::getAdminRouteNamePrefix('admin'),
        )->registerRoutes($collector, $middlewareFactory);

        self::assertEqualsCanonicalizing(
            $names,
            array_map(static fn(RuleSeed $seed): string => $seed->resourceId, new RuleSeeds()->ruleSeeds('admin')),
        );
    }

    #[Test]
    public function followsTheConfiguredAdminName(): void
    {
        $seeds = new RuleSeeds()->ruleSeeds('backoffice');

        self::assertSame('backoffice.acl', $seeds[0]->resourceId);
        self::assertSame('backoffice.acl.role.read', $seeds[1]->resourceId);
        self::assertSame('backoffice.acl', $seeds[1]->parentResourceId);
    }

    #[Test]
    public function grantsTheAclManagerToDeveloperUnderItsAnchor(): void
    {
        $seeds = new RuleSeeds()->ruleSeeds('admin');

        self::assertCount(11, $seeds);
        self::assertContainsOnlyInstancesOf(RuleSeed::class, $seeds);

        foreach ($seeds as $seed) {
            self::assertSame(RuleType::Allow, $seed->type);
            self::assertSame(Role::Developer->value, $seed->roleId);
            self::assertSame([], $seed->assertions);
        }

        self::assertSame('admin.acl', $seeds[0]->resourceId);
        self::assertNull($seeds[0]->parentResourceId);

        foreach (array_slice(
            array : $seeds,
            offset: 1,
        ) as $child) {
            self::assertSame('admin.acl', $child->parentResourceId);
        }

        self::assertSame(
            [
                'admin.acl',
                'admin.acl.role.read',
                'admin.acl.role.add.modal',
                'admin.acl.role.edit.modal',
                'admin.acl.role.create',
                'admin.acl.role.update',
                'admin.acl.role.delete',
                'admin.acl.rule.create',
                'admin.acl.rule.update',
                'admin.acl.rule.delete',
                'admin.acl.rule.delete.modal',
            ],
            array_map(static fn(RuleSeed $seed): string => $seed->resourceId, $seeds),
        );
    }
}

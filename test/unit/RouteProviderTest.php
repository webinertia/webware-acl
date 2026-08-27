<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use Closure;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Webware\Acl\Admin\Middleware\OverviewMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Acl\Admin\RequestHandler\AclOverviewHandler;
use Webware\Acl\Admin\RequestHandler\AddRoleModalHandler;
use Webware\Acl\Admin\RequestHandler\DeleteRuleModalHandler;
use Webware\Acl\Admin\RequestHandler\EditRoleModalHandler;
use Webware\Acl\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\RouteProvider;
use Webware\Htmx\Middleware\DisableBodyMiddleware;

use function array_map;

#[CoversClass(RouteProvider::class)]
final class RouteProviderTest extends TestCase
{
    /** @var list<array{method: string, route: Route}> */
    private array $routes = [];

    /** @var list<list<class-string>> */
    private array $preparedMiddleware = [];

    private RouteCollectorInterface $collector;

    #[Test]
    public function baseRouteCarriesNavigationOptions(): void
    {
        new RouteProvider('admin', 'admin.')->registerRoutes($this->collector, $this->middlewareFactory());

        self::assertSame(
            [
                'navigation' => 'admin',
                'label'      => 'Access Control',
                'icon'       => 'bi-shield-lock',
                'parent'     => null,
                'order'      => 15,
            ],
            $this->routes[0]['route']->getOptions(),
        );
    }

    #[Test]
    public function registerRoutesRegistersExpectedPathsNamesAndMiddleware(): void
    {
        new RouteProvider('admin', 'admin.')->registerRoutes($this->collector, $this->middlewareFactory());

        self::assertSame(
            [
                ['GET',    '/admin',                                              'admin'],
                ['GET',    '/admin/roles',                                        'admin.role.read'],
                ['POST',   '/admin/rule',                                         'admin.rule.create'],
                ['PATCH',  '/admin/rule',                                         'admin.rule.update'],
                ['DELETE', '/admin/rule/{roleId:[^/]+}/{resourceId:[^/]+}',       'admin.rule.delete'],
                ['GET',    '/admin/rule/{roleId:[^/]+}/{resourceId:[^/]+}/modal', 'admin.rule.delete.modal'],
                ['POST',   '/admin/role',                                         'admin.role.create'],
                ['GET',    '/admin/role/modal',                                   'admin.role.add.modal'],
                ['GET',    '/admin/role/{roleId:[^/]+}/modal',                    'admin.role.edit.modal'],
                ['PATCH',  '/admin/role/{roleId:[^/]+}',                          'admin.role.update'],
                ['DELETE', '/admin/role/{roleId:[^/]+}',                          'admin.role.delete'],
            ],
            array_map(
                static fn(array $registered): array => [
                    $registered['method'],
                    $registered['route']->getPath(),
                    $registered['route']->getName(),
                ],
                $this->routes,
            ),
        );

        self::assertSame(
            [
                [OverviewMiddleware::class, AclOverviewHandler::class],
                [RoleListHandler::class],
                [ProcessRuleMiddleware::class, OverviewMiddleware::class, AclOverviewHandler::class],
                [
                    BodyParamsMiddleware::class,
                    ProcessRuleMiddleware::class,
                    OverviewMiddleware::class,
                    AclOverviewHandler::class,
                ],
                [ProcessRuleMiddleware::class, OverviewMiddleware::class, AclOverviewHandler::class],
                [DisableBodyMiddleware::class, DeleteRuleModalHandler::class],
                [ProcessRoleMiddleware::class, RoleListHandler::class],
                [DisableBodyMiddleware::class, AddRoleModalHandler::class],
                [DisableBodyMiddleware::class, EditRoleModalHandler::class],
                [BodyParamsMiddleware::class, ProcessRoleMiddleware::class, RoleListHandler::class],
                [ProcessRoleMiddleware::class, RoleListHandler::class],
            ],
            $this->preparedMiddleware,
        );
    }

    #[Test]
    public function rolesRouteCarriesParentAndOrderOptions(): void
    {
        new RouteProvider('admin', 'admin.')->registerRoutes($this->collector, $this->middlewareFactory());

        self::assertSame(
            [
                'label'  => 'Roles',
                'icon'   => 'bi-shield-lock-fill',
                'parent' => 'admin.acl.read',
                'order'  => 30,
            ],
            $this->routes[1]['route']->getOptions(),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->routes             = [];
        $this->preparedMiddleware = [];
        $this->collector          = $this->createStub(RouteCollectorInterface::class);

        $register = fn(string $method): Closure => function (
            string $path,
            MiddlewareInterface $middleware,
            ?string $name = null,
        ) use ($method): Route {
            $route = new Route($path, $middleware, null, $name);

            $this->routes[] = ['method' => $method, 'route' => $route];

            return $route;
        };

        $this->collector->method('get')->willReturnCallback($register('GET'));
        $this->collector->method('post')->willReturnCallback($register('POST'));
        $this->collector->method('patch')->willReturnCallback($register('PATCH'));
        $this->collector->method('delete')->willReturnCallback($register('DELETE'));
    }

    private function middlewareFactory(): MiddlewareFactoryInterface
    {
        $factory = $this->createStub(MiddlewareFactoryInterface::class);
        $factory->method('prepare')
            ->willReturnCallback(
                function (array $middleware): MiddlewareInterface {
                    $this->preparedMiddleware[] = $middleware;

                    return $this->createStub(MiddlewareInterface::class);
                },
            );

        return $factory;
    }
}

<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl;

use FilesystemIterator;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Webware\Acl\RouteProvider as AclRouteProvider;
use Webware\Admin\RouteProvider as AdminRouteProvider;

use function array_diff;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function dirname;
use function file_get_contents;
use function implode;
use function preg_match_all;
use function sort;
use function str_ends_with;

/**
 * Every adminUrl() name a template asks for has to be a route the providers register. The names are read
 * from the routes themselves, so a rename in a RouteProvider fails here instead of when the page renders.
 */
#[CoversNothing]
final class TemplateRouteNamesTest extends TestCase
{
    private const string ADMIN_NAME = 'admin';

    /** @var list<string> */
    private array $routeNames = [];

    #[Test]
    public function everyAdminUrlNameInTheTemplatesIsARegisteredRoute(): void
    {
        $this->registerRoutes();

        $requested = $this->templateRouteNames();

        static::assertNotSame([], $requested);
        static::assertSame(
            [],
            array_values(array_diff($requested, $this->routeNames)),
            'Templates ask adminUrl() for routes nothing registers. Registered: ' . implode(', ', $this->routeNames),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeNames = [];
    }

    private function registerRoutes(): void
    {
        $collector = $this->createStub(RouteCollectorInterface::class);

        $register = function (string $path, MiddlewareInterface $middleware, ?string $name = null): Route {
            $route = new Route(
                path      : $path,
                middleware: $middleware,
                name      : $name,
            );

            $this->routeNames[] = (string) $route->getName();

            return $route;
        };

        foreach (['get', 'post', 'patch', 'delete'] as $method) {
            $collector->method($method)->willReturnCallback($register);
        }

        $factory = $this->createStub(MiddlewareFactoryInterface::class);
        $factory->method('prepare')->willReturn($this->createStub(MiddlewareInterface::class));

        $prefix = self::ADMIN_NAME . '.';

        new AdminRouteProvider(
            adminBasePath  : self::ADMIN_NAME,
            routeNamePrefix: $prefix,
        )->registerRoutes(
            routeCollector   : $collector,
            middlewareFactory: $factory,
        );
        new AclRouteProvider(
            adminRouteSegment   : self::ADMIN_NAME . '/acl',
            adminRouteNamePrefix: "{$prefix}acl.",
        )->registerRoutes(
            routeCollector   : $collector,
            middlewareFactory: $factory,
        );
    }

    /**
     * The full route name for each adminUrl() call, the way AdminUrl prefixes it with the admin name.
     *
     * @return list<string>
     */
    private function templateRouteNames(): array
    {
        $found    = [];
        $prefix   = self::ADMIN_NAME . '.';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                directory: dirname(__DIR__, levels: 2) . '/templates',
                flags    : FilesystemIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if (! str_ends_with((string) $file, '.phtml')) {
                continue;
            }

            preg_match_all("/adminUrl\\(\\s*'([^']+)'/", (string) file_get_contents((string) $file), $matches);

            $found[] = $matches[1];
        }

        $names = array_values(array_unique(array_map(
            static fn(string $name): string => "{$prefix}{$name}",
            array_merge(...$found),
        )));
        sort($names);

        return $names;
    }
}

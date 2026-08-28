<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Http\Admin\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\AssertionManager;
use Webware\Acl\Http\Admin\Middleware\OverviewMiddleware;
use Webware\Core\AclInterface;
use WebwareTest\Acl\Support\PhpDbAdapterMockTrait;

use function array_keys;

#[CoversClass(OverviewMiddleware::class)]
final class OverviewMiddlewareTest extends TestCase
{
    use PhpDbAdapterMockTrait;

    #[Test]
    public function processBuildsDenyRules(): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRoles')->willReturn(['Admin' => []]);
        $acl->method('hasResource')->willReturnMap([['dashboard', true]]);

        $ruleRepo = $this->createQueryBus($this->createAdapter([
            [
                [
                    'type'             => 'Deny',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'dashboard',
                    'assertions'       => null,
                    'parentResourceId' => null,
                ],
            ],
        ]));

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')->willReturn([$this->route('dashboard', ['GET'])]);

        $handler = $this->capturingHandler();

        new OverviewMiddleware($ruleRepo, $routeCollector, $this->assertionManager())->process(
            new ServerRequest()->withAttribute(AclInterface::class, $acl),
            $handler,
        );

        $viewModel = $handler->received?->getAttribute(OverviewMiddleware::class);

        $rule = $viewModel['protectedRoutes']['dashboard']['rules'][0];
        self::assertSame('Deny', $rule['type']);
        self::assertSame([], $rule['assertions']);
        self::assertFalse($rule['inherited']);
        self::assertNull($rule['inherited_from']);
    }

    #[Test]
    public function processBuildsProtectedAndUnprotectedRoutes(): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRoles')->willReturn(['Admin' => [], 'Manager' => ['Admin']]);
        $acl->method('hasResource')->willReturnMap([
            ['dashboard',   true],
            ['unprotected', false],
        ]);

        $ruleRepo = $this->createQueryBus($this->createAdapter([
            [
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'dashboard',
                    'assertions'       => '["Ownership"]',
                    'parentResourceId' => null,
                ],
            ],
        ]));

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')
            ->willReturn([
                $this->route('dashboard', ['GET']),
                $this->route('unprotected', ['GET']),
            ]);

        $handler = $this->capturingHandler();

        new OverviewMiddleware($ruleRepo, $routeCollector, $this->assertionManager())->process(
            new ServerRequest()->withAttribute(AclInterface::class, $acl),
            $handler,
        );

        $viewModel = $handler->received?->getAttribute(OverviewMiddleware::class);

        self::assertIsArray($viewModel);
        self::assertSame(['dashboard'], array_keys($viewModel['protectedRoutes']));
        self::assertSame(['unprotected'], array_keys($viewModel['unprotectedRoutes']));
        self::assertSame('dashboard', $viewModel['protectedRoutes']['dashboard']['rules'][0]['resourceId']);
        self::assertSame(['Ownership'], $viewModel['protectedRoutes']['dashboard']['rules'][0]['assertions']);
        self::assertSame(['read'], $viewModel['protectedRoutes']['dashboard']['derivedPrivileges']);
        self::assertSame(['all' => 2, 'unprotected' => 1, 'protected' => 1], $viewModel['routeFilters']);
        self::assertSame(
            [['label' => 'Ownership', 'value' => 'Ownership']],
            $viewModel['assertions'],
        );
        self::assertSame(
            [
                ['id' => 0, 'roleId' => 'Admin', 'parents' => []],
                ['id' => 1, 'roleId' => 'Manager', 'parents' => [0]],
            ],
            $viewModel['roleTree'],
        );
        self::assertSame([0 => [1]], $viewModel['roleChildren']);
        self::assertSame([0 => [], 1 => [0]], $viewModel['roleParents']);
    }

    #[Test]
    public function processMarksInheritedRules(): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRoles')->willReturn(['Admin' => []]);
        $acl->method('hasResource')->willReturnMap([
            ['admin', true],
            ['admin.users', true],
        ]);

        $ruleRepo = $this->createQueryBus($this->createAdapter([
            [
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'admin',
                    'assertions'       => null,
                    'parentResourceId' => null,
                ],
                [
                    'type'             => 'Allow',
                    'roleId'           => 'Admin',
                    'resourceId'       => 'admin.users',
                    'assertions'       => null,
                    'parentResourceId' => 'admin',
                ],
            ],
        ]));

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')
            ->willReturn([
                $this->route('admin', ['GET']),
                $this->route('admin.users', ['GET']),
            ]);

        $handler = $this->capturingHandler();

        new OverviewMiddleware($ruleRepo, $routeCollector, $this->assertionManager())->process(
            new ServerRequest()->withAttribute(AclInterface::class, $acl),
            $handler,
        );

        $viewModel = $handler->received?->getAttribute(OverviewMiddleware::class);

        $rule = $viewModel['protectedRoutes']['admin.users']['rules'][0];
        self::assertTrue($rule['inherited']);
        self::assertSame('admin', $rule['inherited_from']);
        self::assertSame('admin.users', $rule['resourceId']);
    }

    #[Test]
    public function processSkipsUnknownParentRoles(): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRoles')->willReturn(['Admin' => ['MissingParent']]);
        $acl->method('hasResource')->willReturnMap([]);

        $ruleRepo = $this->createQueryBus($this->createAdapter([[]]));

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')->willReturn([]);

        $handler = $this->capturingHandler();

        new OverviewMiddleware($ruleRepo, $routeCollector, $this->assertionManager())->process(
            new ServerRequest()->withAttribute(AclInterface::class, $acl),
            $handler,
        );

        $viewModel = $handler->received?->getAttribute(OverviewMiddleware::class);

        self::assertSame([0 => []], $viewModel['roleParents']);
        self::assertSame(
            [['id' => 0, 'roleId' => 'Admin', 'parents' => []]],
            $viewModel['roleTree'],
        );
    }

    #[Test]
    public function processTreatsResourceWithoutRulesAsUnprotected(): void
    {
        $acl = $this->createStub(AclInterface::class);
        $acl->method('getRoles')->willReturn(['Admin' => []]);
        $acl->method('hasResource')->willReturnMap([['ruleless', true]]);

        $ruleRepo = $this->createQueryBus($this->createAdapter([[]]));

        $routeCollector = $this->createStub(RouteCollectorInterface::class);
        $routeCollector->method('getRoutes')->willReturn([$this->route('ruleless', ['POST'])]);

        $handler = $this->capturingHandler();

        new OverviewMiddleware($ruleRepo, $routeCollector, $this->assertionManager())->process(
            new ServerRequest()->withAttribute(AclInterface::class, $acl),
            $handler,
        );

        $viewModel = $handler->received?->getAttribute(OverviewMiddleware::class);

        self::assertSame([], $viewModel['protectedRoutes']);
        self::assertSame(['ruleless'], array_keys($viewModel['unprotectedRoutes']));
        self::assertSame(['POST'], $viewModel['unprotectedRoutes']['ruleless']);
    }

    private function assertionManager(): AssertionManager
    {
        $manager = new AssertionManager(new ServiceManager());
        $manager->configure([
            'aliases'   => ['Ownership' => OwnershipAssertion::class],
            'factories' => [OwnershipAssertion::class => OwnershipAssertion::class],
        ]);

        return $manager;
    }

    private function capturingHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public ?ServerRequestInterface $received = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->received = $request;

                return new EmptyResponse();
            }
        };
    }

    private function route(string $name, array $methods): Route
    {
        return new Route('/', $this->createStub(MiddlewareInterface::class), $methods, $name);
    }
}

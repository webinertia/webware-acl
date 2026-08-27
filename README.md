# webware-acl

[![PHP Version](https://img.shields.io/packagist/php-v/webware/webware-acl)](https://packagist.org/packages/webware/webware-acl)
[![Latest Version](https://img.shields.io/packagist/v/webware/webware-acl)](https://packagist.org/packages/webware/webware-acl)
[![License](https://img.shields.io/github/license/webinertia/webware-acl)](LICENSE)
[![Continuous Integration](https://github.com/webinertia/webware-acl/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/webinertia/webware-acl/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/webinertia/webware-acl/graph/badge.svg)](https://codecov.io/gh/webinertia/webware-acl)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fwebware-acl%2F0.1.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/webware-acl/0.1.x)

A role-based access control (RBAC) library for Mezzio applications. Provides
route-level authorisation backed by a relational database, a file-based cache,
a PSR-14 event-driven build pipeline, and a full Bootstrap 5 administration UI.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.5+ |
| `mezzio/mezzio` | ^3.0 |
| `laminas/laminas-permissions-acl` | ^2.0 |
| `psr/event-dispatcher` | ^1.0 |
| `psr/http-server-middleware` | ^1.0 |
| `webware/webware-core` | ^1.0 |

---

## Quick Start

### 1. Register the ConfigProvider

```php
// config/config.php
$aggregator = new ConfigAggregator([
    \Webware\Acl\ConfigProvider::class,
    // ...
]);
```

### 2. Add IdentityMiddleware to the global pipeline

```php
// config/pipeline.php  (before your routing middleware)
$app->pipe(\Webware\Acl\Middleware\IdentityMiddleware::class);
```

### 3. Protect routes in your module's RouteProvider

```php
use Webware\Acl\Http\Middleware\AuthorizationMiddleware;

$routeCollector->get('/my-module', $middlewareFactory->prepare([
    AuthorizationMiddleware::class,
    MyHandler::class,
]), 'my-module.read');
```

### 4. Register resources, rules, and route mappings

Implement three listener classes in your module (see the
[Integration Guide](docs/integration-guide.md)).

---

## Documentation

| Document | Description |
|---|---|
| [Architecture Blueprint](docs/architecture/blueprint.md) | Full C4 + component diagrams, layer map, design decisions |
| [ACL Build Pipeline](docs/acl-build-pipeline.md) | How `AclBuilder` assembles the ACL, events, caching |
| [Authorization Middleware](docs/authorization-middleware.md) | Per-request access check, decision table, identity flow |
| [Admin UI Workflows](docs/admin-ui-workflows.md) | Role / Resource / Rule / Route map management UI walkthroughs |
| [Integration Guide](docs/integration-guide.md) | Step-by-step: protect a new module's routes |

---

## Package Layout

```
src/webware-acl/
├── composer.json
├── docs/                          ← this documentation tree
│   ├── architecture/
│   │   └── blueprint.md
│   ├── acl-build-pipeline.md
│   ├── authorization-middleware.md
│   ├── admin-ui-workflows.md
│   └── integration-guide.md
├── src/
│   ├── ConfigProvider.php         ← DI wiring + listener registration
│   ├── RouteProvider.php          ← Admin UI routes
│   ├── Acl.php                    ← AclInterface implementation
│   ├── AclBuilder.php             ← DB → Laminas Acl hydration + cache
│   ├── AclInterface.php           ← isAllowed / isAllowedRoute / isAllowedByRouteName
│   ├── Privilege.php              ← READ / CREATE / UPDATE / DELETE constants
│   ├── Admin/
│   │   ├── WriteResult.php        ← Success/Failure request attribute key enum
│   │   ├── Middleware/            ← ProcessRole/Resource/Rule/RouteMapping/Assertion
│   │   └── RequestHandler/       ← AclOverview/RoleList/ResourceList/RuleManager/RouteMapManager
│   ├── Authentication/
│   │   └── DefaultUserFactory.php ← Assigns base role to unauthenticated users
│   ├── Cache/
│   │   ├── AclCacheInterface.php
│   │   └── FileAclCache.php       ← Serialised PHP file at data/cache/acl.cache
│   ├── Container/                 ← DI factories for core services
│   ├── Entity/
│   │   ├── Role.php               ← DB row; implements Laminas RoleInterface
│   │   ├── Resource.php           ← DB row; implements Laminas ResourceInterface
│   │   └── Privilege.php          ← DB row (scoped to a resource)
│   ├── Event/                     ← AclBuildStarted/RolesLoaded/ResourcesLoaded/RulesLoaded/AclBuilt
│   ├── Exception/
│   ├── Listener/                  ← RegisterAclResources/Rules/RouteMappings/OwnershipAssertion
│   ├── Middleware/
│   │   ├── AuthorizationMiddleware.php
│   │   └── IdentityMiddleware.php
│   ├── Repository/
│   │   ├── AclRepositoryInterface.php
│   │   └── AclRepository.php
│   └── Widget/
└── templates/acl/
    ├── admin-acl.phtml            ← Overview dashboard
    ├── admin-roles.phtml
    ├── admin-resources.phtml
    ├── admin-rules.phtml          ← Flat table + hierarchy view
    ├── admin-route-map.phtml
    └── admin-widget.phtml
```

---

## Key Design Decisions

**Why Laminas Permissions ACL?**  
Laminas provides a battle-tested, hierarchical RBAC engine with assertion
support. `webware-acl` wraps it with a persistence layer, caching, and a
PSR-14 event pipeline so host applications never manipulate the Laminas Acl
object directly.

**Why file cache, not Redis/APCu?**  
The file cache (PHP `serialize`) requires zero infrastructure and works in any
PHP environment. The cache is invalidated by a version counter in the database;
the rebuild cost is one serialised file read per request on a cache hit.

**Why PSR-14 events for the build pipeline?**  
Modules register resources, rules, and route mappings without modifying core
ACL code. The event contract is stable; new modules plug in without recompiling
anything.

**Why store route→resource mappings separately from the Laminas Acl?**  
Laminas Acl knows nothing about HTTP routes. Route mappings are a thin
translation table (`route_name → resource_id + privilege_id`) that lives in the
`Acl` wrapper class and is populated by `AclBuilder` after the full event
dispatch.

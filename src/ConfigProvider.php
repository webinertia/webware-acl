<?php

declare(strict_types=1);

namespace Webware\Acl;

use Laminas\InputFilter\InputFilterFactory;
use Laminas\Permissions\Acl\AclInterface as LaminasAclInterface;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Admin\CommandHandler\Container\DeleteRoleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\DeleteRuleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\SaveRoleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\SaveRuleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\UpdateRuleTypeHandlerFactory;
use Webware\Acl\Admin\CommandHandler\DeleteRoleHandler;
use Webware\Acl\Admin\CommandHandler\DeleteRuleHandler;
use Webware\Acl\Admin\CommandHandler\SaveRoleHandler;
use Webware\Acl\Admin\CommandHandler\SaveRuleHandler;
use Webware\Acl\Admin\CommandHandler\UpdateRuleTypeHandler;
use Webware\Acl\Admin\Dashboard\Container\RegisterWidgetListenerFactory;
use Webware\Acl\Admin\Dashboard\RegisterWidgetListener;
use Webware\Acl\Console\Container\InitDBCommandFactory;
use Webware\Acl\Console\InitDBCommand;
use Webware\Acl\Container\AclFactory;
use Webware\Acl\Container\RouteProviderFactory;
use Webware\Acl\Http\Admin\Middleware\Container\OverviewMiddlewareFactory;
use Webware\Acl\Http\Admin\Middleware\Container\ProcessRoleMiddlewareFactory;
use Webware\Acl\Http\Admin\Middleware\Container\ProcessRuleMiddlewareFactory;
use Webware\Acl\Http\Admin\Middleware\OverviewMiddleware;
use Webware\Acl\Http\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\Http\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Acl\Http\Admin\RequestHandler\AclOverviewHandler;
use Webware\Acl\Http\Admin\RequestHandler\AddRoleModalHandler;
use Webware\Acl\Http\Admin\RequestHandler\Container\AclOverviewHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandler\Container\AddRoleModalHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandler\Container\DeleteRuleModalHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandler\Container\EditRoleModalHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandler\Container\ResourceListHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandler\Container\RoleListHandlerFactory;
use Webware\Acl\Http\Admin\RequestHandler\DeleteRuleModalHandler;
use Webware\Acl\Http\Admin\RequestHandler\EditRoleModalHandler;
use Webware\Acl\Http\Admin\RequestHandler\ResourceListHandler;
use Webware\Acl\Http\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\Http\Container\RouteResourceFactoryFactory;
use Webware\Acl\Http\Middleware\AclMiddleware;
use Webware\Acl\Http\Middleware\AuthorizationMiddleware;
use Webware\Acl\Http\Middleware\Container\AclMiddlewareFactory;
use Webware\Acl\Http\Middleware\Container\AuthorizationMiddlewareFactory;
use Webware\Acl\Http\RequestHandler\Container\ForbiddenHandlerFactory;
use Webware\Acl\Http\RequestHandler\ForbiddenHandler;
use Webware\Acl\Http\RequestHandler\ForbiddenHandlerInterface;
use Webware\Acl\Http\RouteResourceFactory;
use Webware\Acl\Http\RouteResourceFactoryInterface;
use Webware\Acl\Query\FetchAclRoleRegistry;
use Webware\Acl\Query\FetchAllRoles;
use Webware\Acl\Query\FetchAllRules;
use Webware\Acl\Query\FetchDistinctResourceIds;
use Webware\Acl\QueryHandler\Container\FetchAclRoleRegistryHandlerFactory;
use Webware\Acl\QueryHandler\Container\FetchAllRolesHandlerFactory;
use Webware\Acl\QueryHandler\Container\FetchAllRulesHandlerFactory;
use Webware\Acl\QueryHandler\Container\FetchDistinctResourceIdsHandlerFactory;
use Webware\Acl\QueryHandler\FetchAclRoleRegistryHandler;
use Webware\Acl\QueryHandler\FetchAllRolesHandler;
use Webware\Acl\QueryHandler\FetchAllRulesHandler;
use Webware\Acl\QueryHandler\FetchDistinctResourceIdsHandler;
use Webware\Acl\Repository\Container\RoleRepositoryFactory;
use Webware\Acl\Repository\Container\RuleRepositoryFactory;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\RuleRepository;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\Console\ConsoleInterface;
use Webware\Core\AclInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;

/**
 * @type AssertionManagerConfig = array{
 *   aliases: array<string, class-string>,
 *   factories: array<class-string, class-string>,
 * }
 * @type BusConfig = array{
 *   command_map: array<class-string, class-string>,
 *   query_map: array<class-string, class-string>,
 *   middleware_pipeline: array<array{middleware: class-string, priority: int}>,
 * }
 * @type Dependencies = array{
 *   aliases: array<class-string, class-string>,
 *   factories: array<class-string, class-string>,
 * }
 * @type DefaultConfig = array{
 *   route_param_map: array<array-key, mixed>,
 *   forbidden_redirect: string,
 *   forbidden_template: null,
 *   admin_route_segment: string,
 *   admin_route_name_prefix: string,
 * }
 * @type InputFilterConfig = array{factories: array<class-string, class-string>}
 * @type Listeners = array<class-string, array<array{listener: class-string, priority: int}>>
 * @type RouteProviders = array{'route-providers': array<class-string>}
 * @type Templates = array{paths: array{acl: array<string>}}
 * @type ValidatorConfig = array{factories: array<class-string, class-string>}
 * @type ConsoleConfig = array{commands: array<string, class-string>}
 * @type ProviderConfig = array{
 *   dependencies: Dependencies,
 *   input_filters: InputFilterConfig,
 *   listeners: Listeners,
 *   router: RouteProviders,
 *   templates: Templates,
 *   Webware\Core\AclInterface: DefaultConfig,
 *   Webware\Acl\AssertionManager: AssertionManagerConfig,
 *   Webware\Console\ConsoleInterface: ConsoleConfig,
 *   Webware\MessageBus\MessageBusInterface: BusConfig,
 *   validators: ValidatorConfig,
 * }
 * @internal
 */
final class ConfigProvider
{
    /**
     * @return AssertionManagerConfig
     */
    public function getAssertionManagerConfig(): array
    {
        return [
            'aliases'   => [
                'Ownership' => Assertion\OwnershipAssertion::class,
            ],
            'factories' => [
                // OwnershipAssertion defines __invoke(): static, making it usable as its own factory key.
                Assertion\OwnershipAssertion::class => Assertion\OwnershipAssertion::class,
            ],
        ];
    }

    /**
     * @return BusConfig
     */
    public function getBusConfig(): array
    {
        return [
            BusProvider::COMMAND_MAP_KEY         => [
                SaveRoleCommand::class       => SaveRoleHandler::class,
                DeleteRoleCommand::class     => DeleteRoleHandler::class,
                DeleteRuleCommand::class     => DeleteRuleHandler::class,
                SaveRuleCommand::class       => SaveRuleHandler::class,
                UpdateRuleTypeCommand::class => UpdateRuleTypeHandler::class,
            ],
            BusProvider::QUERY_MAP_KEY           => [
                FetchAllRules::class            => FetchAllRulesHandler::class,
                FetchDistinctResourceIds::class => FetchDistinctResourceIdsHandler::class,
                FetchAclRoleRegistry::class     => FetchAclRoleRegistryHandler::class,
                FetchAllRoles::class            => FetchAllRolesHandler::class,
            ],
            BusProvider::MIDDLEWARE_PIPELINE_KEY => [
                [
                    'middleware' => MessageHandlerMiddleware::class,
                    'priority'   => BusProvider::DEFAULT_PRIORITY,
                ],
            ],
        ];
    }

    /**
     * @return DefaultConfig
     */
    public function getDefaultConfig(): array
    {
        return [
            'route_param_map'                                    => [],
            'forbidden_redirect'                                 => '/',
            'forbidden_template'                                 => null,
            Container\Configuration::ADMIN_ROUTE_SEGMENT_KEY     => Container\Configuration::ADMIN_ROUTE_SEGMENT_VALUE,
            Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_KEY => Container\Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE,
        ];
    }

    /**
     * @return Dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                AclInterface::class                  => Acl::class,
                ForbiddenHandlerInterface::class     => ForbiddenHandler::class,
                LaminasAclInterface::class           => Acl::class,
                RouteResourceFactoryInterface::class => RouteResourceFactory::class,
            ],
            'factories' => [
                Acl::class                                 => AclFactory::class,
                Assertion\AssertionAggregateFactory::class => Assertion\AssertionAggregateFactoryFactory::class,
                AssertionManager::class                    => Container\AssertionManagerFactory::class,
                RouteResourceFactory::class                => RouteResourceFactoryFactory::class,
                OverviewMiddleware::class                  => OverviewMiddlewareFactory::class,
                ForbiddenHandler::class                    => ForbiddenHandlerFactory::class,
                AclOverviewHandler::class                  => AclOverviewHandlerFactory::class,
                DeleteRuleModalHandler::class              => DeleteRuleModalHandlerFactory::class,
                AddRoleModalHandler::class                 => AddRoleModalHandlerFactory::class,
                EditRoleModalHandler::class                => EditRoleModalHandlerFactory::class,
                AclMiddleware::class                       => AclMiddlewareFactory::class,
                AuthorizationMiddleware::class             => AuthorizationMiddlewareFactory::class,
                RegisterWidgetListener::class              => RegisterWidgetListenerFactory::class,
                ResourceListHandler::class                 => ResourceListHandlerFactory::class,
                RoleListHandler::class                     => RoleListHandlerFactory::class,
                RouteProvider::class                       => RouteProviderFactory::class,
                ProcessRuleMiddleware::class               => ProcessRuleMiddlewareFactory::class,
                ProcessRoleMiddleware::class               => ProcessRoleMiddlewareFactory::class,
                DeleteRoleHandler::class                   => DeleteRoleHandlerFactory::class,
                DeleteRuleHandler::class                   => DeleteRuleHandlerFactory::class,
                SaveRoleHandler::class                     => SaveRoleHandlerFactory::class,
                SaveRuleHandler::class                     => SaveRuleHandlerFactory::class,
                UpdateRuleTypeHandler::class               => UpdateRuleTypeHandlerFactory::class,
                FetchAllRulesHandler::class                => FetchAllRulesHandlerFactory::class,
                FetchDistinctResourceIdsHandler::class     => FetchDistinctResourceIdsHandlerFactory::class,
                FetchAclRoleRegistryHandler::class         => FetchAclRoleRegistryHandlerFactory::class,
                FetchAllRolesHandler::class                => FetchAllRolesHandlerFactory::class,
                RoleRepository::class                      => RoleRepositoryFactory::class,
                RuleRepository::class                      => RuleRepositoryFactory::class,
                InitDBCommand::class                       => InitDBCommandFactory::class,
            ],
        ];
    }

    /**
     * @return InputFilterConfig
     */
    public function getInputFilterConfig(): array
    {
        return [
            'factories' => [
                InputFilter\RuleDataFilter::class => InputFilter\Container\RuleDataFilterFactory::class,
                InputFilter\RoleDataFilter::class => InputFilterFactory::class,
            ],
        ];
    }

    /**
     * @return Listeners
     */
    public function getListeners(): array
    {
        return [
            RegisterWidgetEvent::class => [
                ['listener' => RegisterWidgetListener::class, 'priority' => 1],
            ],
        ];
    }

    /**
     * @return RouteProviders
     */
    public function getRouteProviders(): array
    {
        return [
            'route-providers' => [
                RouteProvider::class,
            ],
        ];
    }

    /**
     * @return Templates
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'acl' => [__DIR__ . '/../templates/acl'],
            ],
        ];
    }

    /**
     * @return ValidatorConfig
     */
    public function getValidatorConfig(): array
    {
        return [
            'factories' => [
                Validator\Assertion::class => Validator\Container\AssertionFactory::class,
            ],
        ];
    }

    /**
     * Returns the configuration array.
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return ProviderConfig
     */
    public function __invoke(): array
    {
        return [
            'dependencies'             => $this->getDependencies(),
            'input_filters'            => $this->getInputFilterConfig(),
            'listeners'                => $this->getListeners(),
            'router'                   => $this->getRouteProviders(),
            'templates'                => $this->getTemplates(),
            AclInterface::class        => $this->getDefaultConfig(),
            AssertionManager::class    => $this->getAssertionManagerConfig(),
            ConsoleInterface::class    => [
                'commands' => [
                    'acl:init-db' => InitDBCommand::class,
                ],
            ],
            MessageBusInterface::class => $this->getBusConfig(),
            'validators'               => $this->getValidatorConfig(),
        ];
    }
}

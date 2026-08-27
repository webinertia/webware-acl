<?php

declare(strict_types=1);

namespace WebwareTest\Acl;

use Laminas\InputFilter\InputFilterFactory;
use Laminas\Permissions\Acl\AclInterface as LaminasAclInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Acl;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Admin\CommandHandler\DeleteRoleHandler;
use Webware\Acl\Admin\CommandHandler\DeleteRuleHandler;
use Webware\Acl\Admin\CommandHandler\SaveRoleHandler;
use Webware\Acl\Admin\CommandHandler\SaveRuleHandler;
use Webware\Acl\Admin\CommandHandler\UpdateRuleTypeHandler;
use Webware\Acl\Admin\Dashboard\RegisterWidgetListener;
use Webware\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\AssertionManager;
use Webware\Acl\ConfigProvider;
use Webware\Acl\Container\Configuration;
use Webware\Acl\Http\RequestHandlers\ForbiddenHandler;
use Webware\Acl\Http\RequestHandlers\ForbiddenHandlerInterface;
use Webware\Acl\Http\RouteResourceFactory;
use Webware\Acl\Http\RouteResourceFactoryInterface;
use Webware\Acl\InputFilter\Container\RuleDataFilterFactory;
use Webware\Acl\InputFilter\RoleDataFilter;
use Webware\Acl\InputFilter\RuleDataFilter;
use Webware\Acl\Repository\RoleRepository;
use Webware\Acl\Repository\RuleRepository;
use Webware\Acl\RouteProvider;
use Webware\Acl\Validator\Assertion;
use Webware\Acl\Validator\Container\AssertionFactory;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\Core\AclInterface;
use Webware\MessageBus\ConfigProvider as BusProvider;
use Webware\MessageBus\MessageBusInterface;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function getAssertionManagerConfigRegistersOwnershipAssertion(): void
    {
        self::assertSame(
            [
                'aliases'   => ['Ownership' => OwnershipAssertion::class],
                'factories' => [OwnershipAssertion::class => OwnershipAssertion::class],
            ],
            new ConfigProvider()->getAssertionManagerConfig(),
        );
    }

    #[Test]
    public function getBusConfigMapsCommandsAndPipeline(): void
    {
        $config = new ConfigProvider()->getBusConfig();

        self::assertSame(
            [
                SaveRoleCommand::class       => SaveRoleHandler::class,
                DeleteRoleCommand::class     => DeleteRoleHandler::class,
                DeleteRuleCommand::class     => DeleteRuleHandler::class,
                SaveRuleCommand::class       => SaveRuleHandler::class,
                UpdateRuleTypeCommand::class => UpdateRuleTypeHandler::class,
            ],
            $config[BusProvider::COMMAND_MAP_KEY],
        );
        self::assertCount(2, $config[BusProvider::MIDDLEWARE_PIPELINE_KEY]);
    }

    #[Test]
    public function getDefaultConfigSetsDefaults(): void
    {
        $config = new ConfigProvider()->getDefaultConfig();

        self::assertSame([], $config['route_param_map']);
        self::assertSame('/', $config['forbidden_redirect']);
        self::assertNull($config['forbidden_template']);
        self::assertSame(
            Configuration::ADMIN_ROUTE_SEGMENT_VALUE,
            $config[Configuration::ADMIN_ROUTE_SEGMENT_KEY],
        );
        self::assertSame(
            Configuration::ADMIN_ROUTE_NAME_PREFIX_VALUE,
            $config[Configuration::ADMIN_ROUTE_NAME_PREFIX_KEY],
        );
    }

    #[Test]
    public function getDependenciesRegistersAliasesAndFactories(): void
    {
        $deps = new ConfigProvider()->getDependencies();

        self::assertSame(
            [
                AclInterface::class                  => Acl::class,
                ForbiddenHandlerInterface::class     => ForbiddenHandler::class,
                LaminasAclInterface::class           => Acl::class,
                RouteResourceFactoryInterface::class => RouteResourceFactory::class,
            ],
            $deps['aliases'],
        );
        self::assertCount(26, $deps['factories']);
        self::assertArrayHasKey(RoleRepository::class, $deps['factories']);
        self::assertArrayHasKey(RuleRepository::class, $deps['factories']);
    }

    #[Test]
    public function getInputFilterConfigRegistersDataFilters(): void
    {
        self::assertSame(
            [
                'factories' => [
                    RuleDataFilter::class => RuleDataFilterFactory::class,
                    RoleDataFilter::class => InputFilterFactory::class,
                ],
            ],
            new ConfigProvider()->getInputFilterConfig(),
        );
    }

    #[Test]
    public function getListenersRegistersDashboardWidgetListener(): void
    {
        self::assertSame(
            [
                RegisterWidgetEvent::class => [
                    ['listener' => RegisterWidgetListener::class, 'priority' => 1],
                ],
            ],
            new ConfigProvider()->getListeners(),
        );
    }

    #[Test]
    public function getRouteProvidersRegistersAclRouteProvider(): void
    {
        self::assertSame(
            ['route-providers' => [RouteProvider::class]],
            new ConfigProvider()->getRouteProviders(),
        );
    }

    #[Test]
    public function getTemplatesRegistersAclTemplatePath(): void
    {
        $templates = new ConfigProvider()->getTemplates();

        self::assertArrayHasKey('paths', $templates);
        self::assertArrayHasKey('acl', $templates['paths']);
        self::assertCount(1, $templates['paths']['acl']);
        self::assertStringEndsWith('/templates/acl', $templates['paths']['acl'][0]);
    }

    #[Test]
    public function getValidatorConfigRegistersAssertionValidator(): void
    {
        self::assertSame(
            ['factories' => [Assertion::class => AssertionFactory::class]],
            new ConfigProvider()->getValidatorConfig(),
        );
    }

    #[Test]
    public function invokeMergesAllConfigSections(): void
    {
        $config = (new ConfigProvider())();

        self::assertSame(new ConfigProvider()->getDependencies(), $config['dependencies']);
        self::assertSame(new ConfigProvider()->getInputFilterConfig(), $config['input_filters']);
        self::assertSame(new ConfigProvider()->getListeners(), $config['listeners']);
        self::assertSame(new ConfigProvider()->getRouteProviders(), $config['router']);
        self::assertSame(new ConfigProvider()->getTemplates(), $config['templates']);
        self::assertSame(new ConfigProvider()->getValidatorConfig(), $config['validators']);
        self::assertSame(new ConfigProvider()->getDefaultConfig(), $config[AclInterface::class]);
        self::assertSame(new ConfigProvider()->getAssertionManagerConfig(), $config[AssertionManager::class]);
        self::assertSame(new ConfigProvider()->getBusConfig(), $config[MessageBusInterface::class]);
    }
}

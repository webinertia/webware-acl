<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Support;

use Laminas\Filter\FilterPluginManager;
use Laminas\InputFilter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ConfigProvider as ValidatorConfigProvider;
use Laminas\Validator\ValidatorPluginManager;
use Webware\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\AssertionManager;
use Webware\Acl\InputFilter\RoleDataFilter;
use Webware\Acl\InputFilter\RuleDataFilter;
use Webware\Acl\Validator;

/**
 * Builds fully-wired, real Laminas input filters for unit tests, mirroring
 * the production `input_filters` / `validators` / `AssertionManager` wiring.
 */
final class InputFilterHelper
{
    public static function roleDataFilter(): RoleDataFilter
    {
        $container = self::container();
        $filter    = new RoleDataFilter($container->get(InputFilter\Factory::class));
        $filter->init();

        return $filter;
    }

    public static function ruleDataFilter(): RuleDataFilter
    {
        $container = self::container();
        $filter    = new RuleDataFilter($container->get(InputFilter\Factory::class));
        $filter->init();

        return $filter;
    }

    private static function container(): ServiceManager
    {
        $container = new ServiceManager(new ValidatorConfigProvider()->getDependencyConfig());

        $validatorPluginManager = new ValidatorPluginManager($container);

        $container->setService(FilterPluginManager::class, new FilterPluginManager($container));
        $container->setService(ValidatorPluginManager::class, $validatorPluginManager);
        $container->setService(
            InputFilter\InputFilterPluginManager::class,
            new InputFilter\InputFilterPluginManager($container),
        );

        // Registers the shared InputFilter\Factory into $container and reuses
        // the plugin managers registered above.
        InputFilter\Factory::new($container);

        $assertionManager = new AssertionManager(new ServiceManager());
        $assertionManager->configure([
            'aliases'   => ['Ownership' => OwnershipAssertion::class],
            'factories' => [OwnershipAssertion::class => OwnershipAssertion::class],
        ]);
        $container->setService(AssertionManager::class, $assertionManager);

        $validatorPluginManager->setFactory(
            Validator\Assertion::class,
            Validator\Container\AssertionFactory::class,
        );

        return $container;
    }
}

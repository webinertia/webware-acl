<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl\Support;

use Laminas\Filter\FilterPluginManager;
use Laminas\InputFilter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ConfigProvider as ValidatorConfigProvider;
use Laminas\Validator\ValidatorPluginManager;
use Psr\Container\ContainerInterface;
use Webware\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\AssertionManager;
use Webware\Acl\InputFilter\RoleDataFilter;
use Webware\Acl\InputFilter\RuleDataFilter;
use Webware\Acl\InputFilter\RuleDeleteFilter;
use Webware\Acl\Validator;

/**
 * Builds a fully-wired, real Laminas input-filter plugin manager for the
 * acl admin filters, mirroring the production `input_filters` / `validators`
 * / `AssertionManager` configuration.
 */
final class FilterManagerFactory
{
    public static function create(): InputFilter\InputFilterPluginManager
    {
        // Wire the laminas-validator dependencies (ValidatorChainFactory and
        // friends) so every input can build its validator chain.
        $container = new ServiceManager(new ValidatorConfigProvider()->getDependencyConfig());

        $validatorPluginManager   = new ValidatorPluginManager($container);
        $inputFilterPluginManager = new InputFilter\InputFilterPluginManager($container);

        $container->setService(FilterPluginManager::class, new FilterPluginManager($container));
        $container->setService(ValidatorPluginManager::class, $validatorPluginManager);
        $container->setService(InputFilter\InputFilterPluginManager::class, $inputFilterPluginManager);

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

        $inputFilterPluginManager->setFactory(
            RoleDataFilter::class,
            static fn(ContainerInterface $c): RoleDataFilter => new RoleDataFilter(
                $c->get(InputFilter\Factory::class),
            ),
        );
        $inputFilterPluginManager->setFactory(
            RuleDataFilter::class,
            static fn(ContainerInterface $c): RuleDataFilter => new RuleDataFilter(
                $c->get(InputFilter\Factory::class),
            ),
        );
        $inputFilterPluginManager->setFactory(
            RuleDeleteFilter::class,
            static fn(ContainerInterface $c): RuleDeleteFilter => new RuleDeleteFilter(
                $c->get(InputFilter\Factory::class),
            ),
        );

        return $inputFilterPluginManager;
    }
}

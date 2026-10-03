<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Console\Seed\RuleSeedCollector;
use Webware\Acl\Container\Configuration;
use Webware\Admin\Container\Configuration as AdminConfiguration;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Exception\ConfigurationException;

use function get_debug_type;

final readonly class RuleSeedCollectorFactory
{
    /**
     * @throws ConfigurationException
     */
    private static function provider(mixed $candidate): RuleSeedProviderInterface
    {
        if (! $candidate instanceof RuleSeedProviderInterface) {
            throw ConfigurationException::forInvalidConfigType(
                'rule_seed_providers',
                RuleSeedProviderInterface::class,
                get_debug_type($candidate),
                self::class,
            );
        }

        return $candidate;
    }

    /**
     * @throws ConfigurationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): RuleSeedCollector
    {
        $config = Configuration::getConfig($container, self::class);

        /** @var list<class-string> $classes */
        $classes   = $config['rule_seed_providers'] ?? [];
        $providers = [];

        foreach ($classes as $class) {
            $providers[] = self::provider($container->get($class));
        }

        return new RuleSeedCollector(
            providers: $providers,
            adminName: AdminConfiguration::getAdminName($container, self::class),
        );
    }
}

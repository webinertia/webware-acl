<?php

declare(strict_types=1);

namespace Webware\Acl\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\AssertionManager;
use Webware\Core\Exception;

final readonly class AssertionManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception\ExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AssertionManager
    {
        $config = Configuration::getAssertionManagerConfig($container, self::class);

        return new AssertionManager($container, $config);
    }
}

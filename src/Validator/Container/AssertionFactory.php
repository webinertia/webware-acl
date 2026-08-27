<?php

declare(strict_types=1);

namespace Webware\Acl\Validator\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\AssertionManager;
use Webware\Acl\Validator\Assertion;

final readonly class AssertionFactory
{
    /**
     * @param array<string, mixed>|null $options
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): Assertion {
        return new Assertion(
            $container->get(AssertionManager::class),
            $options,
        );
    }
}

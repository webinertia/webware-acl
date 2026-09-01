<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Exception\LogicException;
use Webware\Acl\Console\InitDBCommand;

final readonly class InitDBCommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws LogicException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): InitDBCommand
    {
        return new InitDBCommand(
            $container->get(AdapterInterface::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace Webware\Acl\Console\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\SchemaFactory;
use PhpDb\Sql\Exception\ExceptionInterface as SqlException;
use Psl\Type\Exception\ExceptionInterface as PslTypeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Acl\Console\Seed\RuleSeeder;
use Webware\Acl\Console\Seed\RuleSeedValidator;
use Webware\Acl\Repository\Schema;

final readonly class RuleSeederFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SqlException
     * @throws PslTypeException
     */
    public function __invoke(ContainerInterface $container): RuleSeeder
    {
        return new RuleSeeder(
            adapter  : $container->get(AdapterInterface::class),
            table    : $container->get(SchemaFactory::class)(Schema::Rules),
            metadata : $container->get(MetadataInterface::class),
            validator: new RuleSeedValidator(),
        );
    }
}

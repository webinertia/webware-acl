<?php

declare(strict_types=1);

namespace WebwareTestIntegration\Acl\Console;

use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\ConfigProvider as PhpDbConfigProvider;
use PhpDb\Mysql\ConfigProvider as MysqlConfigProvider;
use PhpDb\Mysql\Pdo\Driver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webware\Acl\Console\InitDBCommand;

use function getenv;

/**
 * Verifies the command creates the schema and seeds data against a live
 * MySQL database (the `mysql` compose service / CI `db-image`).
 */
#[CoversClass(InitDBCommand::class)]
final class InitDBCommandIntegrationTest extends TestCase
{
    #[Test]
    public function commandCreatesSchemaAndSeedsBaseData(): void
    {
        $adapter = $this->createAdapter();

        $tester = new CommandTester(new InitDBCommand($adapter));
        $tester->execute(['--drop' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $roles = [];
        foreach ($adapter->executeQuery('SELECT roleId FROM acl_role ORDER BY id') as $row) {
            $roles[] = $row['roleId'];
        }
        self::assertSame(['Guest', 'Member', 'Administrator', 'Developer'], $roles);

        $resources = [];
        foreach ($adapter->executeQuery('SELECT resourceId FROM acl_rule ORDER BY id') as $row) {
            $resources[] = $row['resourceId'];
        }
        self::assertCount(11, $resources);
        self::assertSame('acl.manager', $resources[0]);
        self::assertSame('acl.manager.rule.delete.modal', $resources[10]);
    }

    private function createAdapter(): AdapterInterface
    {
        $config = [
            AdapterInterface::class => [
                'driver'     => Driver::class,
                'connection' => [
                    'dbname'   => getenv('TESTS_ADAPTER_MYSQL_DATABASE'),
                    'host'     => getenv('TESTS_ADAPTER_MYSQL_HOSTNAME'),
                    'port'     => getenv('TESTS_ADAPTER_MYSQL_PORT'),
                    'username' => getenv('TESTS_ADAPTER_MYSQL_USERNAME'),
                    'password' => getenv('TESTS_ADAPTER_MYSQL_PASSWORD'),
                ],
            ],
        ];

        $container = new ServiceManager();
        $container->configure(new PhpDbConfigProvider()->getDependencies());
        $container->configure(new MysqlConfigProvider()->getDependencies());
        $container->setService('config', $config);

        return $container->get(AdapterInterface::class);
    }
}

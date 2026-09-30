<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PhpDb\Sql\Platform\PlatformDecoratorInterface;
use PhpDb\Sql\PreparableSqlInterface;
use PhpDb\Sql\SqlInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webware\Acl\Console\InitDBCommand;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Acl\RuleType;
use Webware\Core\Role;
use WebwareTest\Acl\Support\SeedRunnerTrait;

use function count;

#[CoversClass(InitDBCommand::class)]
final class InitDBCommandTest extends TestCase
{
    use SeedRunnerTrait;

    #[Test]
    public function configureDefinesCommandNameDescriptionAndDropOption(): void
    {
        $command = new InitDBCommand(
            adapter: $this->createStub(AdapterInterface::class),
            runner : $this->seedRunner(),
        );

        $definition = $command->getDefinition();

        self::assertSame('acl:init-db', $command->getName());
        self::assertSame(
            'Create the ACL database schema and seed the base roles and rules',
            $command->getDescription(),
        );
        self::assertTrue($definition->hasOption('drop'));
        self::assertFalse($definition->getOption('drop')->acceptValue());
    }

    #[Test]
    public function executeCreatesSchemaAndSeedsWithoutDropping(): void
    {
        $command = new InitDBCommand(
            adapter: $this->createAdapter(
                queryCalls         : 2,
                statementExecutions: count(Role::getRoles()),
            ),
            runner : $this->seedRunner(),
        );

        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Creating ACL schema', $tester->getDisplay());
        self::assertStringContainsString('Seeding ACL roles', $tester->getDisplay());
        self::assertStringContainsString('Seeding ACL rules', $tester->getDisplay());
        self::assertStringContainsString('ACL database initialized', $tester->getDisplay());
        self::assertStringNotContainsString('Dropping existing ACL tables', $tester->getDisplay());
    }

    #[Test]
    public function executeDropsTablesWhenRequested(): void
    {
        $command = new InitDBCommand(
            adapter: $this->createAdapter(
                queryCalls         : 4,
                statementExecutions: count(Role::getRoles()),
            ),
            runner : $this->seedRunner(),
        );

        $tester = new CommandTester($command);
        $tester->execute(['--drop' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Dropping existing ACL tables', $tester->getDisplay());
        self::assertStringContainsString('Seeding ACL roles', $tester->getDisplay());
        self::assertStringContainsString('Seeding ACL rules', $tester->getDisplay());
        self::assertStringContainsString('ACL database initialized', $tester->getDisplay());
    }

    #[Test]
    public function executeFailsWhenThePublishedRulesAreInvalid(): void
    {
        $provider = $this->createStub(RuleSeedProviderInterface::class);
        $provider->method('ruleSeeds')
            ->willReturn([
                new RuleSeed(
                    type      : RuleType::Allow,
                    roleId    : 'Guest',
                    resourceId: 'nowhere',
                ),
            ]);

        $command = new InitDBCommand(
            adapter: $this->createAdapter(
                queryCalls         : 2,
                statementExecutions: count(Role::getRoles()),
            ),
            runner: $this->seedRunner(
                providers: [$provider],
                tables: ['acl_rule'],
            ),
        );

        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Anchor node "nowhere"', $tester->getDisplay());
        self::assertStringNotContainsString('ACL database initialized', $tester->getDisplay());
    }

    private function createAdapter(int $queryCalls, int $statementExecutions): AdapterInterface
    {
        $platform  = $this->createStub(PlatformInterface::class);
        $driver    = $this->createStub(DriverInterface::class);
        $decorator = $this->createStubForIntersectionOfInterfaces([
            PlatformDecoratorInterface::class,
            SqlInterface::class,
            PreparableSqlInterface::class,
        ]);

        $decorator->method('setSubject')->willReturnSelf();
        $decorator->method('getSqlString')->willReturn('');
        $decorator->method('prepareStatement')->willReturnArgument(1);

        $statement = $this->createMock(StatementInterface::class);
        $statement->expects($this->exactly($statementExecutions))->method('execute');

        $driver->method('createStatement')->willReturn($statement);
        $platform->method('getSqlPlatformDecorator')->willReturn($decorator);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn($platform);
        $adapter->expects($this->exactly($queryCalls))
            ->method('query')
            ->with($this->anything(), AdapterInterface::QUERY_MODE_EXECUTE)
            ->willReturn($this->createStub(ResultInterface::class));

        return $adapter;
    }
}

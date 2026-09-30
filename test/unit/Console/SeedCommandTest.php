<?php

declare(strict_types=1);

namespace WebwareTest\Acl\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Webware\Acl\Console\SeedCommand;
use Webware\Core\Acl\RuleSeed;
use Webware\Core\Acl\RuleSeedProviderInterface;
use Webware\Core\Acl\RuleType;
use WebwareTest\Acl\Support\SeedRunnerTrait;

#[CoversClass(SeedCommand::class)]
#[CoversMethod(SeedCommand::class, '__construct')]
#[CoversMethod(SeedCommand::class, 'execute')]
final class SeedCommandTest extends TestCase
{
    use SeedRunnerTrait;

    #[Test]
    public function declaresItsNameAndDescription(): void
    {
        $command = new SeedCommand($this->seedRunner());

        self::assertSame('acl:seed', $command->getName());
        self::assertSame('Write the ACL rules every installed component publishes', $command->getDescription());
    }

    #[Test]
    public function failsAndListsEveryViolation(): void
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

        $tester = new CommandTester(new SeedCommand($this->seedRunner(
            providers: [$provider],
            tables: ['acl_rule'],
        )));
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Anchor node "nowhere"', $tester->getDisplay());
    }

    #[Test]
    public function reportsTheSeededCount(): void
    {
        $tester = new CommandTester(new SeedCommand($this->seedRunner(tables: ['acl_rule'])));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Seeded 0 ACL rules.', $tester->getDisplay());
    }

    #[Test]
    public function warnsWithoutFailingWhenTheTableIsMissing(): void
    {
        $tester = new CommandTester(new SeedCommand($this->seedRunner()));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('does not exist yet', $tester->getDisplay());
    }
}

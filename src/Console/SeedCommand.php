<?php

declare(strict_types=1);

namespace Webware\Acl\Console;

use JsonException;
use Override;
use PhpDb\Exception\ExceptionInterface as PhpDbException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webware\Acl\Console\Seed\SeedRunner;

#[AsCommand(
    name       : 'acl:seed',
    description: 'Write the ACL rules every installed component publishes',
)]
final class SeedCommand extends Command
{
    /**
     * @throws ConsoleLogicException
     */
    public function __construct(
        private readonly SeedRunner $runner,
    ) {
        parent::__construct();
    }

    /**
     * @throws JsonException
     * @throws PhpDbException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->runner->run();

        if ($result->hasViolations()) {
            foreach ($result->violations as $violation) {
                $output->writeln("<error>{$violation}</error>");
            }

            return Command::FAILURE;
        }

        if ($result->tableMissing) {
            $output->writeln(
                '<comment>The ACL rule table does not exist yet; nothing was seeded. Run acl:init-db first.</comment>',
            );

            return Command::SUCCESS;
        }

        $output->writeln("Seeded {$result->seeded} ACL rules.");

        return Command::SUCCESS;
    }
}

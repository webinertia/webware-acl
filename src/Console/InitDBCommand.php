<?php

declare(strict_types=1);

namespace Webware\Acl\Console;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Exception\InvalidArgumentException as SqlInvalidArgumentException;
use PhpDb\Sql\InsertIgnore;
use PhpDb\Sql\Sql;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name       : 'acl:init-db',
    description: 'Create the ACL database schema and seed the base roles and rules',
)]
final class InitDBCommand extends Command
{
    private readonly AclSchema $schema;

    /**
     * @throws ConsoleLogicException
     */
    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {
        $this->schema = new AclSchema();

        parent::__construct();
    }

    /**
     * @throws ConsoleInvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            name       : 'drop',
            mode       : InputOption::VALUE_NONE,
            description: 'Drop the ACL tables before recreating them',
        );
    }

    /**
     * @throws ConsoleInvalidArgumentException
     * @throws SqlInvalidArgumentException
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = new Sql($this->adapter);

        if (true === $input->getOption('drop')) {
            $output->writeln('Dropping existing ACL tables...');
            foreach ($this->schema->dropTables() as $table) {
                $this->executeDdl($sql, $table);
            }
        }

        $output->writeln('Creating ACL schema...');
        $this->executeDdl($sql, $this->schema->roleTable());
        $this->executeDdl($sql, $this->schema->ruleTable());

        $output->writeln('Seeding ACL roles...');
        foreach ($this->schema->roleSeeds() as $seed) {
            $this->executeInsert($sql, table: 'acl_role', row: $seed);
        }

        $output->writeln('Seeding ACL rules...');
        foreach ($this->schema->ruleSeeds() as $seed) {
            $this->executeInsert($sql, table: 'acl_rule', row: $seed);
        }

        $output->writeln('ACL database initialized.');

        return Command::SUCCESS;
    }

    /**
     * @throws SqlInvalidArgumentException
     */
    private function executeDdl(Sql $sql, CreateTable|DropTable $ddl): void
    {
        $this->adapter->query(
            $sql->buildSqlString($ddl),
            AdapterInterface::QUERY_MODE_EXECUTE,
        );
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws SqlInvalidArgumentException
     */
    private function executeInsert(Sql $sql, string $table, array $row): void
    {
        $sql->prepareStatementForSqlObject(
            new InsertIgnore(table: $table)->values($row),
        )->execute();
    }
}

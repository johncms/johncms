<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Database\Migrations\MigrationGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'make:migration',
    description: 'Create an empty migration in the core or in a module',
)]
final class MakeMigrationCommand extends Command
{
    public function __construct(private readonly MigrationGenerator $generator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            <<<'HELP'
            Creates a migration file named after the current moment, so that it runs after the ones
            already there.

              <info>make:migration system create_widgets_table --table=widgets --create</info>
              <info>make:migration forum add_slug_to_sections --table=forum_sections</info>
              <info>make:migration news move_articles_to_sections</info>
            HELP
        )
            ->addArgument(
                name: 'source',
                mode: InputArgument::REQUIRED,
                description: 'Where the migration belongs: system, or the name of a module'
            )
            ->addArgument(
                name: 'name',
                mode: InputArgument::REQUIRED,
                description: 'What the migration does, for example add_slug_to_sections'
            )
            ->addOption(
                name: 'table',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The table the migration works on'
            )
            ->addOption(
                name: 'create',
                mode: InputOption::VALUE_NONE,
                description: 'The migration creates the table rather than changing it'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $table = $input->getOption('table');

        try {
            $path = $this->generator->create(
                source: (string) $input->getArgument('source'),
                name: (string) $input->getArgument('name'),
                table: is_string($table) && $table !== '' ? $table : null,
                creatingTable: $input->getOption('create') === true,
            );
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $io->success('Migration created: ' . $this->relative($path));

        return self::SUCCESS;
    }

    private function relative(string $path): string
    {
        return str_starts_with($path, ROOT_PATH) ? substr($path, strlen(ROOT_PATH)) : $path;
    }
}

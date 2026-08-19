<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Database\Migrations\ConsoleMigrationReporter;
use Johncms\Database\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'migrate',
    description: 'Apply the migrations the database has not been through yet',
)]
final class MigrateCommand extends Command
{
    public function __construct(private readonly Migrator $migrator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Applies every migration missing from the journal, the core first and the modules after it.')
            ->addOption(
                name: 'source',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Only the migrations of this source: system, or the name of a module'
            )
            ->addOption(
                name: 'dry-run',
                mode: InputOption::VALUE_NONE,
                description: 'Say what would be applied and change nothing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $this->source($input);

        try {
            $pending = $this->migrator->pending($source);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($pending === []) {
            $io->success('The database is up to date.');

            return self::SUCCESS;
        }

        if ($input->getOption('dry-run') === true) {
            $io->section(sprintf('%d migration(s) would be applied', count($pending)));
            foreach ($pending as $migration) {
                $io->text(sprintf('  %s / %s', $migration->source, $migration->name));
            }

            return self::SUCCESS;
        }

        try {
            $applied = $this->migrator->run($source, new ConsoleMigrationReporter($io));
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $io->success(sprintf('The database is up to date. Migrations applied: %d.', count($applied)));

        return self::SUCCESS;
    }

    private function source(InputInterface $input): ?string
    {
        $source = $input->getOption('source');

        return is_string($source) && $source !== '' ? $source : null;
    }
}

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

/**
 * A tool of development. Undoing a step forward throws away what it carried, and most migrations
 * refuse to be undone at all, so a site that is not in debug mode has to say --force out loud.
 */
#[AsCommand(
    name: 'migrate:rollback',
    description: 'Undo the last batch of migrations (for development)',
)]
final class MigrateRollbackCommand extends Command
{
    public function __construct(private readonly Migrator $migrator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'source',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Only the migrations of this source: system, or the name of a module'
        )
            ->addOption(
                name: 'step',
                mode: InputOption::VALUE_REQUIRED,
                description: 'How many batches to undo',
                default: '1'
            )
            ->addOption(
                name: 'force',
                mode: InputOption::VALUE_NONE,
                description: 'Roll back even though this site is not in debug mode'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (! DEBUG && $input->getOption('force') !== true) {
            $io->error(
                'Rolling back undoes a step the data has already been through, and what that step carried is lost.'
                . ' This site is not in debug mode: repeat with --force if that is really what you mean.'
            );

            return self::FAILURE;
        }

        $source = $input->getOption('source');
        $step = (int) $input->getOption('step');

        try {
            $rolledBack = $this->migrator->rollback(
                is_string($source) && $source !== '' ? $source : null,
                max(1, $step),
                new ConsoleMigrationReporter($io)
            );
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($rolledBack === []) {
            $io->success('There was nothing to roll back.');

            return self::SUCCESS;
        }

        $io->success(sprintf('Migrations rolled back: %d.', count($rolledBack)));

        return self::SUCCESS;
    }
}

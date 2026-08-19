<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Database\Migrations\MigrationStatus;
use Johncms\Database\Migrations\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'migrate:status',
    description: 'Show which migrations this database has been through and which are waiting',
)]
final class MigrateStatusCommand extends Command
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
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getOption('source');

        try {
            $statuses = $this->migrator->status(is_string($source) && $source !== '' ? $source : null);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($statuses === []) {
            $io->warning('There are no migrations.');

            return self::SUCCESS;
        }

        $io->table(
            ['Source', 'Version', 'Migration', 'Status', 'Applied', 'Note'],
            array_map($this->row(...), $statuses)
        );

        $pending = count(array_filter($statuses, static fn (MigrationStatus $status): bool => ! $status->isApplied));
        if ($pending === 0) {
            $io->success('The database is up to date.');

            return self::SUCCESS;
        }

        $io->warning(sprintf('%d migration(s) are waiting. Run "migrate" to apply them.', $pending));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function row(MigrationStatus $status): array
    {
        $note = '';
        if (! $status->fileExists) {
            $note = 'the file is gone';
        } elseif (! $status->checksumMatches) {
            $note = 'the file was edited after it ran';
        }

        return [
            $status->source,
            $status->version,
            $status->name,
            $status->isApplied ? 'applied' : 'waiting',
            $status->appliedAt ?? '',
            $note,
        ];
    }
}

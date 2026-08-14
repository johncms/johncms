<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Authorization\LegacyRightsMigration;
use Johncms\Console\OneTimeTaskTracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'auth:migrate-rights',
    description: 'One-time: turn the numeric users.rights into role assignments',
)]
#[AsAdminTask(
    title: 'Convert access levels into roles',
    description: 'Gives every member of staff the role their old access level stood for. Runs once.',
)]
final class AuthMigrateRightsCommand extends Command
{
    public function __construct(
        private readonly LegacyRightsMigration $migration,
        private readonly OneTimeTaskTracker $tracker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'force',
            mode: InputOption::VALUE_NONE,
            description: 'Run again, leaving accounts that already have roles alone'
        );
        $this->addOption(
            name: 'reset',
            mode: InputOption::VALUE_NONE,
            description: 'Run again and redo accounts that already have roles, discarding what was arranged by hand'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reset = (bool) $input->getOption('reset');
        $force = (bool) $input->getOption('force') || $reset;

        if ($this->tracker->isCompleted($this->getName()) && ! $force) {
            $io->warning('This one-time task has already been completed. Use --force to run it again.');

            return self::SUCCESS;
        }

        try {
            $backup = $this->writeSnapshot();
        } catch (Throwable $exception) {
            $io->error('Could not write the backup of the current access levels: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $io->text(sprintf('The current access levels were saved to %s', $backup));

        try {
            $report = $this->migration->migrate($reset);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $io->success(sprintf('Roles granted: %d. Accounts left alone: %d.', $report->granted, $report->skipped));

        if ($report->hasUnrecognised()) {
            $io->warning('Some accounts had an access level that stands for no documented role.');
            $io->text('They were given the closest role below it, which never grants more than they had.');
            $io->table(
                ['Access level', 'Accounts'],
                array_map(
                    static fn (int $rights, array $ids): array => [$rights, implode(', ', $ids)],
                    array_keys($report->unrecognised),
                    $report->unrecognised
                )
            );
            $io->text('Check these accounts in the admin panel and give them the roles they should have.');
        }

        $this->tracker->markCompleted($this->getName());

        return self::SUCCESS;
    }

    /**
     * The numbers as they were, written before anything else happens. RightsMirror recomputes
     * the column from the roles afterwards, so an undocumented value is rewritten and otherwise
     * gone for good.
     *
     * @return string Path of the file written.
     */
    private function writeSnapshot(): string
    {
        $path = DATA_PATH . 'user-rights-backup-' . date('Y-m-d-His') . '.json';
        $snapshot = json_encode($this->migration->snapshot(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $snapshot) === false) {
            throw new \RuntimeException(sprintf('Can not write %s', $path));
        }

        return $path;
    }
}

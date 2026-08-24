<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Writes down what the site is already running.
 *
 * The state file is generated, so a site that predates it — or one whose file was lost — has
 * nothing recorded while every module of the release is quietly working. This records exactly
 * that, and nothing more: it never installs anything, because installing means migrations, and a
 * module that has not been through them is not something a bookkeeping command may declare
 * installed.
 */
#[AsCommand(
    name: 'module:sync',
    description: 'Record the modules of this release in the state file, and drop what is gone',
)]
#[AsAdminTask(
    title: 'Refresh the list of modules',
    description: 'Writes down the modules of this release and forgets the ones whose files are gone. Installs nothing.',
)]
final class ModuleSyncCommand extends Command
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleRepositoryInterface $modules,
        private readonly ModuleStateStore $state,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $records = $this->state->all();
        $installed = $this->registry->installed();
        $added = [];
        $forgotten = [];

        foreach ($installed as $key => $manifest) {
            if (isset($records[$key])) {
                continue;
            }

            $records[$key] = new ModuleStateRecord(
                key: $key,
                alias: $manifest->alias,
                version: $manifest->version,
                installedAt: time(),
            );
            $added[] = $key;
        }

        foreach ($records as $key => $record) {
            if ($this->modules->find($key) !== null) {
                continue;
            }

            unset($records[$key]);
            $forgotten[] = $key;
        }

        try {
            $this->state->save($records);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($added !== []) {
            $io->writeln(sprintf(' <info>Recorded</info>: %s', implode(', ', $added)));
        }

        if ($forgotten !== []) {
            $io->writeln(sprintf(' <comment>Forgotten (no files)</comment>: %s', implode(', ', $forgotten)));
        }

        $io->success(sprintf('The state of %d modules is written down.', count($records)));

        return self::SUCCESS;
    }
}

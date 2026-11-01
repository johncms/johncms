<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Database\Migrations\ConsoleMigrationReporter;
use Johncms\Database\Migrations\MigrationRunnerInterface;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Modules\ModuleDependencyGraph;
use Johncms\Modules\ModuleInstallerFactory;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleStateStore;
use Johncms\Scheduler\ScheduleMutexInterface;
use Johncms\Updates\DeclarationSynchronizerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'system:update', description: 'Apply migrations, synchronize declarations and update loaded modules')]
final class SystemUpdateCommand extends Command
{
    /** @param iterable<DeclarationSynchronizerInterface> $synchronizers */
    public function __construct(
        private readonly MigrationRunnerInterface $migrator,
        private readonly PendingMigrations $pending,
        private readonly ModuleRegistry $modules,
        private readonly ModuleStateStore $state,
        private readonly ModuleInstallerFactory $installers,
        private readonly ScheduleMutexInterface $mutex,
        private readonly CacheClearCommand $clearCache,
        private readonly iterable $synchronizers,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $lock = $this->mutex->acquire('johncms.system.update');
        if ($lock === null) {
            $io->error('Cannot acquire the system update lock. Another update may be running.');
            return self::FAILURE;
        }

        $step = 'migrations';
        try {
            $this->migrator->run(reporter: new ConsoleMigrationReporter($io));
            $this->pending->forget();
            $modules = $this->modules->enabled();
            $owners = ['system', ...(new ModuleDependencyGraph($modules))->ordered()];
            $synchronizers = is_array($this->synchronizers) ? $this->synchronizers : iterator_to_array($this->synchronizers);
            foreach ($synchronizers as $synchronizer) {
                if (! in_array($synchronizer->owner(), $owners, true)) {
                    throw new RuntimeException('Unknown declaration owner: ' . $synchronizer->owner());
                }
            }
            foreach ($owners as $owner) {
                foreach ($synchronizers as $synchronizer) {
                    if ($synchronizer->owner() === $owner) {
                        $step = 'synchronize ' . $owner;
                        $io->text($step);
                        $synchronizer->synchronize();
                    }
                }
            }
            foreach (array_slice($owners, 1) as $key) {
                $step = 'update hook ' . $key;
                $io->text($step);
                $manifest = $modules[$key];
                $version = $manifest->version ?? CMS_VERSION;
                $this->installers->create($manifest)?->update($this->state->find($key)->version ?? CMS_VERSION, $version);
            }
            foreach ($owners as $owner) {
                foreach ($synchronizers as $synchronizer) {
                    if ($synchronizer->owner() === $owner) {
                        $step = 'validate ' . $owner;
                        $synchronizer->validate();
                    }
                }
            }
            $step = 'clear cache';
            if ($this->clearCache->run(new ArrayInput([]), $output) !== self::SUCCESS) {
                throw new RuntimeException('Cache clearing failed.');
            }
        } catch (Throwable $exception) {
            $io->error($step . ': ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            $this->mutex->release($lock);
        }
        $io->success('The system is up to date.');
        return self::SUCCESS;
    }
}

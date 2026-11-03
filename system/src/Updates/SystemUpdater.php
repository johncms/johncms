<?php

declare(strict_types=1);

namespace Johncms\Updates;

use Johncms\Database\Migrations\ConsoleMigrationReporter;
use Johncms\Database\Migrations\MigrationRunnerInterface;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Modules\ModuleDependencyGraph;
use Johncms\Modules\ModuleInstallerFactory;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleStateStore;
use Johncms\Console\Commands\CacheClearCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class SystemUpdater
{
    /** @param iterable<DeclarationSynchronizerInterface> $synchronizers */
    public function __construct(
        private readonly MigrationRunnerInterface $migrator,
        private readonly PendingMigrations $pending,
        private readonly ModuleRegistry $modules,
        private readonly ModuleStateStore $state,
        private readonly ModuleInstallerFactory $installers,
        private readonly SystemUpdateLock $lock,
        private readonly SystemUpdateStateInterface $completion,
        private readonly CacheClearCommand $clearCache,
        private readonly iterable $synchronizers,
        private readonly SystemOperationContext $systemContext = new SystemOperationContext(),
        private readonly ?\Johncms\Sitemap\SitemapGenerator $sitemap = null,
    ) {
    }

    public function run(InputInterface $input, OutputInterface $output, ?string $module = null): void
    {
        $this->lock->run(function () use ($input, $output, $module): void {
            $modules = $this->modules->enabled();
            if ($module !== null && ! isset($modules[$module])) {
                throw new RuntimeException('Module is not loaded: ' . $module);
            }
            $io = new SymfonyStyle($input, $output);
            $step = 'migrations';
            try {
                $this->completion->begin($module);
                $this->migrator->run($module === null ? null : $modules[$module]->alias, reporter: new ConsoleMigrationReporter($io));
                $this->pending->forget();
                $owners = ['system', ...($module === null ? (new ModuleDependencyGraph($modules))->ordered() : [$module])];
                $synchronizers = is_array($this->synchronizers) ? $this->synchronizers : iterator_to_array($this->synchronizers);
                foreach ($synchronizers as $synchronizer) {
                    if ($synchronizer->owner() !== 'system' && ! isset($modules[$synchronizer->owner()])) {
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
                    $this->systemContext->runTrusted(fn () => $this->installers->create($manifest)?->update($this->state->find($key)->version ?? CMS_VERSION, $version), fn (string $message) => $io->text($message));
                }
                foreach ($owners as $owner) {
                    foreach ($synchronizers as $synchronizer) {
                        if ($synchronizer->owner() === $owner) {
                            $step = 'validate ' . $owner;
                            $synchronizer->validate();
                        }
                    }
                }
                if ($this->sitemap !== null && $module === null) {
                    $step = 'generate sitemap';
                    $this->systemContext->runTrusted($this->sitemap->generate(...));
                }
                $step = 'clear cache';
                if ($this->clearCache->run(new ArrayInput([]), $output) !== Command::SUCCESS) {
                    throw new RuntimeException('Cache clearing failed.');
                }
            } catch (Throwable $exception) {
                throw new RuntimeException($step . ': ' . $exception->getMessage(), previous: $exception);
            }
            $this->completion->complete($module);
        });
    }
}

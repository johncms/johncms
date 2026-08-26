<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Modules\ModuleInstallService;
use Johncms\Modules\ModuleOperationResult;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleStatus;
use Johncms\Modules\Package\ModulePackageException;
use Johncms\Modules\Package\ModulePackageInstaller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'module:install',
    description: 'Install a module: one lying in the modules directory, or one from a zip archive',
)]
final class ModuleInstallCommand extends ModuleLifecycleCommand
{
    public function __construct(
        private readonly ModuleInstallService $service,
        private readonly ModulePackageInstaller $packages,
        private readonly ModuleRegistry $registry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::OPTIONAL, 'Key of the module: vendor/name')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Zip archive to install the module from')
            ->addOption('demo', null, InputOption::VALUE_NONE, 'Also install the demo data of the module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('module');
        $archive = $input->getOption('from');
        $result = new ModuleOperationResult();

        $wasInstalled = false;

        if (is_string($archive) && $archive !== '') {
            try {
                $key = $this->packages->extractKeyOnly($archive);

                // Whether this archive is an installation or an update has to be settled before
                // the files are replaced: afterwards the module on disk is the new one either way.
                $known = $this->registry->find($key);
                $wasInstalled = $known !== null && $known->status !== ModuleStatus::Discovered;

                $key = $this->packages->extract($archive);
                $result->done('unpack the archive', sprintf('"%s" is now in the modules directory', $key));

                // The files appeared after the registry had already worked out what is on disk.
                $this->registry->forget();
            } catch (ModulePackageException $exception) {
                return $this->report($input, $output, $result->failed('unpack the archive', $exception->getMessage()));
            }
        }

        if (! is_string($key) || $key === '') {
            return $this->report(
                $input,
                $output,
                $result->failed('install', 'Name the module to install, or point at an archive with --from.')
            );
        }

        // An archive of a module the site already has is an update, not a second installation:
        // the files have just been replaced, and what is left is the migrations and the hooks.
        $operation = $wasInstalled
            ? $this->service->update($key)
            : $this->service->install($key, $input->getOption('demo') === true);

        foreach ($operation->steps() as $step) {
            $step['outcome'] === 'failed'
                ? $result->failed($step['step'], (string) $step['detail'])
                : ($step['outcome'] === 'skipped'
                    ? $result->skipped($step['step'], $step['detail'])
                    : $result->done($step['step'], $step['detail']));
        }

        return $this->report($input, $output, $result);
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Modules\ModuleInstallService;
use Johncms\AdminTasks\AsAdminTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'module:uninstall',
    description: 'Take a module off this site; its files stay where they are',
)]
// Queued from the modules section when the operation is asked to run in the background: a module
// with heavy migrations does not fit into the time a web request is given on a modest host.
// Not listed on the maintenance screen — it needs to be told which module.
#[AsAdminTask(
    title: 'Remove a module',
    description: 'Takes a module off the site; its files stay where they are.',
    background: true,
    listed: false,
)]
final class ModuleUninstallCommand extends ModuleLifecycleCommand
{
    public function __construct(private readonly ModuleInstallService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Key of the module: vendor/name')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Also undo its migrations, deleting its data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->report($input, $output, $this->service->uninstall((string) $input->getArgument('module'), $input->getOption('purge') === true));
    }
}

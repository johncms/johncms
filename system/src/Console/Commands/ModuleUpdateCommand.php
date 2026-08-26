<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Modules\ModuleInstallService;
use Johncms\AdminTasks\AsAdminTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'module:update',
    description: 'Bring an installed module up to the version now on disk',
)]
// Queued from the modules section when the operation is asked to run in the background: a module
// with heavy migrations does not fit into the time a web request is given on a modest host.
// Not listed on the maintenance screen — it needs to be told which module.
#[AsAdminTask(
    title: 'Update a module',
    description: 'Runs the migrations of a module and its update hook.',
    background: true,
    listed: false,
)]
final class ModuleUpdateCommand extends ModuleLifecycleCommand
{
    public function __construct(private readonly ModuleInstallService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Key of the module: vendor/name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->report($input, $output, $this->service->update((string) $input->getArgument('module')));
    }
}

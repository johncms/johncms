<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Modules\ModuleInstallService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'module:install',
    description: 'Install a module that lies in the modules directory',
)]
final class ModuleInstallCommand extends ModuleLifecycleCommand
{
    public function __construct(private readonly ModuleInstallService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Key of the module: vendor/name')
            ->addOption('demo', null, InputOption::VALUE_NONE, 'Also install the demo data of the module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->report($input, $output, $this->service->install((string) $input->getArgument('module'), $input->getOption('demo') === true));
    }
}

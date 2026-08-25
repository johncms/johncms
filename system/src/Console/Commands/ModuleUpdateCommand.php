<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Modules\ModuleInstallService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'module:update',
    description: 'Bring an installed module up to the version now on disk',
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

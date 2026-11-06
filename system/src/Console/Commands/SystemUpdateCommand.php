<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AdminTaskGroup;
use Johncms\AdminTasks\AsAdminTask;
use Johncms\Updates\SystemUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'system:update', description: 'Apply migrations, synchronize declarations and update loaded modules')]
#[AsAdminTask(title: 'Update the system', description: 'Applies migrations, synchronizes declarations and completes module updates.', background: true, group: AdminTaskGroup::Update)]
final class SystemUpdateCommand extends Command
{
    public function __construct(private readonly SystemUpdater $updater)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(name: 'module', mode: InputOption::VALUE_REQUIRED, description: 'Complete the update of one loaded module');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $module = $input->getOption('module');
            $this->updater->run($input, $output, is_string($module) ? $module : null);
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());
            return self::FAILURE;
        }
        $io->success('The system is up to date.');
        return self::SUCCESS;
    }
}

<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Database\Migration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';

    protected function configure(): void
    {
        $this->setDescription('Run or rollback migrations')
            ->setHelp('The command allows you to run and rollback migrations.')
            ->addOption('pretend', null, InputOption::VALUE_OPTIONAL, 'Show SQL queries without performing migration')
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Force the migrations to be run so they can be rolled back individually. The number of migrations to be reverted when running rollback command')
            ->addArgument('action', InputArgument::OPTIONAL, 'run or rollback', 'run')
            ->addArgument('module_name', InputArgument::OPTIONAL, 'The module for which you want to run or rollback migrations. If not specified, migrations of all modules will be performed.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @psalm-suppress PossiblyInvalidArgument
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrations = new Migration();
        $migrations->setMigratorOutput($output);

        $options = [];
        $action = $input->getArgument('action');
        $module_name = $input->getArgument('module_name');
        $pretend = $input->getOption('pretend');
        $step = $input->getOption('step');

        if ($pretend) {
            $options['pretend'] = (bool) $pretend;
        }
        if ($step) {
            $options['step'] = (int) $step;
        }

        if ($action === 'run') {
            $migrations->run($module_name, $options);
        } elseif ($action === 'rollback') {
            $migrations->rollback($module_name, $options);
        }

        return Command::SUCCESS;
    }
}

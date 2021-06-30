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

use Exception;
use Johncms\Database\Migration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrationCommand extends Command
{
    protected static $defaultName = 'make:migration';

    protected function configure(): void
    {
        $this->setDescription('Creates a new migration')
            ->setHelp('This command allows you to create a migration.')
            ->addArgument('module', InputArgument::REQUIRED, 'The module in which the migration will be created.')
            ->addArgument('name', InputArgument::REQUIRED, 'Migration name.')
            ->addArgument('table_name', InputArgument::OPTIONAL, 'The name of the table that will be specified in the migration code.')
            ->addArgument('create', InputArgument::OPTIONAL, 'true - migration to create a table, false - migration to update a table');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @psalm-suppress PossiblyInvalidArgument
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        try {
            $migration = new Migration();
            $table = $input->getArgument('table_name') ?? null;
            $create = ($input->getArgument('create') === 'true');
            $migration_name = $migration->create($input->getArgument('name'), $input->getArgument('module'), $table, $create);
            if (! empty($migration_name)) {
                $style->success('Migration was created: ' . $migration_name);
            }
        } catch (Exception $exception) {
            $style->error($exception->getMessage());
        }

        return Command::SUCCESS;
    }
}

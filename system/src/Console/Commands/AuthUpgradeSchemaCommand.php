<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Schema\AuthSchema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Brings an existing installation up to the schema a fresh one gets from the installer.
 *
 * Deliberately safe to run again: AuthSchema creates what is missing and leaves the rest alone,
 * so this is not guarded by the one-time task tracker — unlike the data migrations, it has
 * nothing to do twice.
 */
#[AsCommand(
    name: 'auth:upgrade-schema',
    description: 'Create the tables of the authentication layer that are missing',
)]
#[AsAdminTask(
    title: 'Update the authentication tables',
    description: 'Creates the database tables the sign-in system needs. Safe to run more than once.',
)]
final class AuthUpgradeSchemaCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $schema = Capsule::schema();

        try {
            AuthSchema::create($schema);
        } catch (Throwable $exception) {
            $io->error('Could not update the authentication tables: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $io->success('The authentication tables are up to date.');

        return self::SUCCESS;
    }
}

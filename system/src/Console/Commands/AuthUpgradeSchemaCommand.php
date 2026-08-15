<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Illuminate\Database\Schema\Builder;
use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Schema\AuthSchema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Brings an existing installation up to what a fresh one gets from the installer: the tables of
 * the authentication layer and the built-in roles.
 *
 * Deliberately safe to run again. Both halves create what is missing and leave the rest alone,
 * so this is not guarded by the one-time task tracker — unlike the data migrations, it has
 * nothing to do twice, and a site that configured its roles keeps what it configured.
 */
#[AsCommand(
    name: 'auth:upgrade-schema',
    description: 'Create the missing tables and built-in roles of the authentication layer',
)]
#[AsAdminTask(
    title: 'Update the authentication tables',
    description: 'Creates the database tables and built-in roles the sign-in system needs. Safe to run more than once.',
)]
final class AuthUpgradeSchemaCommand extends Command
{
    public function __construct(
        private readonly RoleSeeder $roleSeeder,
        private readonly Builder $schema,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            AuthSchema::create($this->schema);
            $created = $this->roleSeeder->seed();
        } catch (Throwable $exception) {
            $io->error('Could not update the authentication tables: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $io->success(
            $created === []
                ? 'The authentication tables are up to date.'
                : sprintf('The authentication tables are up to date. Roles created: %s.', implode(', ', $created))
        );

        return self::SUCCESS;
    }
}

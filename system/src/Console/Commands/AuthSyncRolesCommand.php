<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Authorization\DefaultPermissionsApplier;
use Johncms\Auth\Authorization\RoleSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Brings the built-in roles of a site into line with what this version of the CMS declares.
 *
 * Run after an update: a release that adds a role or a module that adds a permission changes what
 * the built-in roles are supposed to have, and only a fresh installation gets that from the
 * installer. Nothing about it is one-time, which is why it is not a migration — what it applies
 * is assembled from the permissions the installed modules declare right now, not from a list
 * frozen years ago.
 *
 * Both halves only add. A role that is already there keeps the permissions it has, and a role the
 * site created itself is not touched at all.
 */
#[AsCommand(
    name: 'auth:sync-roles',
    description: 'Create the built-in roles this version declares and grant them the permissions they are missing',
)]
#[AsAdminTask(
    title: 'Synchronise roles and permissions',
    description: 'Creates the built-in roles of this version and grants them the permissions they are missing. Only adds, never takes away. Safe to run after every update.',
)]
final class AuthSyncRolesCommand extends Command
{
    public function __construct(
        private readonly RoleSeeder $seeder,
        private readonly DefaultPermissionsApplier $applier,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $created = $this->seeder->seed();
            $granted = $this->applier->apply();
        } catch (Throwable $exception) {
            $io->error('Could not synchronise the roles: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($created !== []) {
            $io->writeln(sprintf(' <info>Roles created</info>: %s', implode(', ', $created)));
        }

        foreach ($granted as $slug => $permissions) {
            $io->writeln(sprintf(' <info>%s</info> was granted: %s', $slug, implode(', ', $permissions)));
        }

        if ($created === [] && $granted === []) {
            $io->success('The roles already have everything this version gives them.');

            return self::SUCCESS;
        }

        $io->success('The roles are up to date.');

        return self::SUCCESS;
    }
}

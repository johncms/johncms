<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Authorization\LegacyRightsMigration;
use Johncms\Auth\Authorization\ModuleAccessMigration;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Database\Schema\SchemaInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Carries a site coming from 9.9 over to roles: the numeric users.rights becomes role
 * assignments, and the mod_* settings become permissions of the built-in roles.
 *
 * The three steps are one command because they only make sense together and in this order — the
 * roles have to exist before an account can be given one, and the settings only mean something
 * while the numbers they lived alongside are still there.
 *
 * Whether there is anything to do is read off the database rather than remembered in a file: as
 * long as users.rights is there the conversion has not been finished, and once the column has
 * been dropped it can never be needed again. That matters for the second step in particular — it
 * brings the roles back to what the mod_* settings say, revoking whatever was arranged in the
 * panel afterwards, and must not be repeated on a site that has moved on.
 */
#[AsCommand(
    name: 'auth:migrate-legacy-access',
    description: 'One-time: turn the numeric access levels and the mod_* settings of 9.9 into roles and permissions',
)]
#[AsAdminTask(
    title: 'Convert the old access system',
    description: 'For a site coming from 9.9: gives every member of staff the role their access level stood for, and turns the per-module access settings into permissions. Does nothing once the old columns are gone.',
)]
final class AuthMigrateLegacyAccessCommand extends Command
{
    private const string LEGACY_COLUMN = 'rights';

    public function __construct(
        private readonly RoleSeeder $seeder,
        private readonly LegacyRightsMigration $rights,
        private readonly ModuleAccessMigration $moduleAccess,
        private readonly SchemaInterface $schema,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'reset',
            mode: InputOption::VALUE_NONE,
            description: 'Redo the accounts that already have roles, discarding what was arranged by hand'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (! $this->schema->hasColumn('users', self::LEGACY_COLUMN)) {
            $io->success('There is nothing to convert: this site has no numeric access levels left.');

            return self::SUCCESS;
        }

        try {
            // The roles have to be there before an account can be given one. A site that already
            // has them keeps what it has.
            $created = $this->seeder->seed();
            $report = $this->rights->migrate((bool) $input->getOption('reset'));
            $changes = $this->moduleAccess->apply((array) config('johncms'));
        } catch (Throwable $exception) {
            $io->error('Could not convert the old access system: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($created !== []) {
            $io->writeln(sprintf(' <info>Roles created</info>: %s', implode(', ', $created)));
        }

        $io->writeln(sprintf(' <info>Accounts given a role</info>: %d', $report->granted));

        if ($report->skipped > 0) {
            $io->writeln(sprintf(' <comment>Left alone</comment> (they already had roles): %d', $report->skipped));
        }

        foreach ($report->unrecognised as $rights => $userIds) {
            $io->writeln(
                sprintf(
                    ' <comment>Access level %d</comment> is not one this version knows; %d account(s) were given the nearest role below it: %s',
                    $rights,
                    count($userIds),
                    implode(', ', $userIds)
                )
            );
        }

        if ($changes !== []) {
            $io->table(
                ['Role', 'Granted', 'Revoked'],
                array_map(
                    static fn (string $slug, array $change): array => [
                        $slug,
                        implode(', ', $change['granted']),
                        implode(', ', $change['revoked']),
                    ],
                    array_keys($changes),
                    $changes
                )
            );
        }

        $io->success(
            'The old access system has been converted. Check the roles in the panel, and drop the columns it'
            . ' left behind with auth:drop-legacy-columns when you are satisfied.'
        );

        return self::SUCCESS;
    }
}

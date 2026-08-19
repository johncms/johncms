<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\AuthTables;
use Johncms\Console\OneTimeTaskTracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Drops the columns of the access system that was replaced by the roles.
 *
 * The last step of the update, and the only one that cannot be undone: it deletes data. It
 * refuses to run before auth:migrate-rights has turned the numbers into role assignments —
 * dropping users.rights first would leave a site with no staff and no way to work out who they
 * had been.
 */
#[AsCommand(
    name: 'auth:drop-legacy-columns',
    description: 'One-time: drop users.rights and the other columns the roles replaced',
)]
#[AsAdminTask(
    title: 'Drop the columns of the old access system',
    description: 'Deletes users.rights and the other leftovers of the numeric access levels. Run it after the access levels have been migrated to roles.',
)]
final class AuthDropLegacyColumnsCommand extends Command
{
    /**
     * The migration that has to have happened first: it is the one that reads users.rights.
     */
    private const REQUIRES = 'auth:migrate-rights';

    /** @var array<string, list<string>> Columns to drop, per table. */
    private const COLUMNS = [
        'users'          => [
            // The access level itself, and the mirror of it that kept the two systems in step.
            'rights',
            // Counted the failed sign-in attempts of an account; the throttle counts them per
            // address and per account in the cache instead, and forgets them on its own.
            'failed_login',
            // The password recovery code and its deadline, replaced by password_reset_tokens.
            'rest_code',
            'rest_time',
        ],
        AuthTables::ROLES => [
            // Which number a role stood for. Only the migration of the numbers needed it.
            'legacy_rights',
        ],
    ];

    public function __construct(
        private readonly OneTimeTaskTracker $tracker,
        private readonly Builder $schema,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (! $this->tracker->isCompleted(self::REQUIRES)) {
            $io->error(
                sprintf(
                    'Run %s first: it is what turns the numbers of this column into roles, and it cannot be run afterwards.',
                    self::REQUIRES
                )
            );

            return self::FAILURE;
        }

        try {
            $dropped = $this->drop($this->schema);
            $this->tracker->markCompleted((string) $this->getName());
        } catch (Throwable $exception) {
            $io->error('Could not drop the columns: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($dropped === []) {
            $io->success('The columns of the old access system are already gone.');

            return self::SUCCESS;
        }

        foreach ($dropped as $table => $columns) {
            $io->writeln(sprintf(' <info>%s</info>: %s', $table, implode(', ', $columns)));
        }

        $io->success('The columns of the old access system are gone.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, list<string>> What was actually dropped, per table.
     */
    private function drop(Builder $schema): array
    {
        $dropped = [];

        foreach (self::COLUMNS as $table => $columns) {
            if (! $schema->hasTable($table)) {
                continue;
            }

            $present = array_values(array_filter(
                $columns,
                static fn (string $column): bool => $schema->hasColumn($table, $column)
            ));

            if ($present === []) {
                continue;
            }

            $schema->table($table, static function (Blueprint $blueprint) use ($present): void {
                $blueprint->dropColumn($present);
            });

            $dropped[$table] = $present;
        }

        return $dropped;
    }
}

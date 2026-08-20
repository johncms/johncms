<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Database\Schema\TableDefinition;
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
 * refuses while any member of staff still has an access level and no role — dropping the column
 * then would leave a site with no staff and no way to work out who they had been.
 */
#[AsCommand(
    name: 'auth:drop-legacy-columns',
    description: 'One-time: drop users.rights and the other columns the roles replaced',
)]
#[AsAdminTask(
    title: 'Drop the columns of the old access system',
    description: 'Deletes users.rights and the other leftovers of the numeric access levels. Refuses while anyone still has an access level and no role.',
)]
final class AuthDropLegacyColumnsCommand extends Command
{
    /** @var array<string, list<string>> Columns to drop, per table. */
    private const array COLUMNS = [
        'users' => [
            // The access level itself, and the mirror of it that kept the two systems in step.
            'rights',
            // Counted the failed sign-in attempts of an account; the throttle counts them per
            // address and per account in the cache instead, and forgets them on its own.
            'failed_login',
            // The password recovery code and its deadline, replaced by password_reset_tokens.
            'rest_code',
            'rest_time',
        ],
    ];

    public function __construct(
        private readonly SchemaInterface $schema,
        private readonly ConnectionInterface $db,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $unconverted = $this->unconvertedStaff();

        if ($unconverted > 0) {
            $io->error(
                sprintf(
                    'Run auth:migrate-legacy-access first: %d account(s) still have an access level and no role, and'
                    . ' the column that says which one is what would be deleted here.',
                    $unconverted
                )
            );

            return self::FAILURE;
        }

        try {
            $dropped = $this->drop();
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
     * Members of staff the conversion has not reached: they have an access level above an
     * ordinary user and hold no role at all.
     */
    private function unconvertedStaff(): int
    {
        if (! $this->schema->hasColumn('users', 'rights')) {
            return 0;
        }

        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS total FROM users'
            . ' LEFT JOIN user_roles ON user_roles.user_id = users.id'
            . ' WHERE users.rights > 0 AND user_roles.user_id IS NULL'
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<string, list<string>> What was actually dropped, per table.
     */
    private function drop(): array
    {
        $dropped = [];

        foreach (self::COLUMNS as $table => $columns) {
            if (! $this->schema->hasTable($table)) {
                continue;
            }

            $present = array_values(array_filter(
                $columns,
                fn (string $column): bool => $this->schema->hasColumn($table, $column)
            ));

            if ($present === []) {
                continue;
            }

            $this->schema->alter($table, static function (TableDefinition $definition) use ($present): void {
                $definition->dropColumn(...$present);
            });

            $dropped[$table] = $present;
        }

        return $dropped;
    }
}

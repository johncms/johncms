<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Authorization\ModuleAccessMigration;
use Johncms\Console\OneTimeTaskTracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Reads the old "who may use this module" settings and writes what they meant into the roles.
 *
 * Needed once, when the mod_* keys stop being what the code asks about. Unlike the command that
 * applies the defaults, this one also takes permissions away — that is the whole point of it for
 * a site whose forum was open to registered visitors only.
 */
#[AsCommand(
    name: 'auth:migrate-module-access',
    description: 'One-time: turn the mod_* settings into permissions of the guest and the user roles',
)]
#[AsAdminTask(
    title: 'Move the module access settings into the roles',
    description: 'Reads the old per-module access settings and grants the matching permissions to the built-in roles.',
)]
final class AuthMigrateModuleAccessCommand extends Command
{
    public function __construct(
        private readonly ModuleAccessMigration $migration,
        private readonly OneTimeTaskTracker $tracker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'force',
            mode: InputOption::VALUE_NONE,
            description: 'Run again, applying the settings that are covered now'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->tracker->isCompleted($this->getName()) && ! $input->getOption('force')) {
            $io->warning('This one-time task has already been completed. Use --force to run it again.');

            return self::SUCCESS;
        }

        try {
            $changes = $this->migration->apply((array) config('johncms'));
            $this->tracker->markCompleted((string) $this->getName());
        } catch (Throwable $exception) {
            $io->error('Could not migrate the module access settings: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($changes === []) {
            $io->success('The roles already say what the module settings said.');

            return self::SUCCESS;
        }

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
        $io->success('The module access settings are now permissions of the roles.');

        return self::SUCCESS;
    }
}

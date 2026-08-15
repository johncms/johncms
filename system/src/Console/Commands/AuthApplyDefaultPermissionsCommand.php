<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Authorization\DefaultPermissionsApplier;
use Johncms\Console\OneTimeTaskTracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Gives the built-in roles of an existing installation the permissions a fresh one is created
 * with. Needed once, when the numeric access levels stop being what the code asks about.
 *
 * Guarded like the other data migrations: it only ever adds, but a site that revoked a default
 * on purpose should not have it handed back by an absent-minded second run. Use --force after an
 * update that declares new permissions.
 */
#[AsCommand(
    name: 'auth:apply-default-permissions',
    description: 'One-time: grant the built-in roles the permissions they have by default',
)]
#[AsAdminTask(
    title: 'Grant the built-in roles their default permissions',
    description: 'Gives the built-in roles what a freshly installed site grants them. Only adds, never takes away.',
)]
final class AuthApplyDefaultPermissionsCommand extends Command
{
    public function __construct(
        private readonly DefaultPermissionsApplier $applier,
        private readonly OneTimeTaskTracker $tracker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'force',
            mode: InputOption::VALUE_NONE,
            description: 'Run again, granting the defaults that are missing now'
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
            $granted = $this->applier->apply();
            $this->tracker->markCompleted((string) $this->getName());
        } catch (Throwable $exception) {
            $io->error('Could not grant the default permissions: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($granted === []) {
            $io->success('The built-in roles already carry their default permissions.');

            return self::SUCCESS;
        }

        $io->table(
            ['Role', 'Granted'],
            array_map(
                static fn (string $slug, array $keys): array => [$slug, implode(', ', $keys)],
                array_keys($granted),
                $granted
            )
        );
        $io->success('The built-in roles are up to date.');

        return self::SUCCESS;
    }
}

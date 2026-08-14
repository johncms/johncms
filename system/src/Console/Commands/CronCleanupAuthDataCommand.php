<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Auth\Session\ExpiredAuthDataCleaner;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'auth:cleanup',
    description: 'Delete sessions that stopped working and expired password recovery links',
)]
#[AsScheduledTask(expression: '20 4 * * *', withoutOverlapping: true)]
#[AsAdminTask(
    title: 'Clean up sign-in data',
    description: 'Removes long-dead sessions and expired password recovery links.',
)]
final class CronCleanupAuthDataCommand extends Command
{
    public function __construct(
        private readonly ExpiredAuthDataCleaner $cleaner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'retention-days',
            mode: InputOption::VALUE_REQUIRED,
            description: 'How long a session that stopped working is kept before it is deleted',
            default: 30
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $retentionDays = max(0, (int) $input->getOption('retention-days'));

        $removed = $this->cleaner->clean(retention: $retentionDays * 86400);

        $io->success(
            sprintf(
                'Removed %d session(s) and %d password recovery link(s).',
                $removed['sessions'],
                $removed['reset_tokens']
            )
        );

        return self::SUCCESS;
    }
}

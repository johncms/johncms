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
    description: 'Delete sessions that stopped working, expired password recovery links and old audit entries',
)]
#[AsScheduledTask(expression: '20 4 * * *', withoutOverlapping: true)]
#[AsAdminTask(
    title: 'Clean up sign-in data',
    description: 'Removes long-dead sessions, expired password recovery links and old audit entries.',
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
        $this->addOption(
            name: 'event-retention-days',
            mode: InputOption::VALUE_REQUIRED,
            description: 'How long an entry of the sign-in audit trail is kept',
            default: 180
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $retentionDays = max(0, (int) $input->getOption('retention-days'));
        $eventRetentionDays = max(0, (int) $input->getOption('event-retention-days'));

        $removed = $this->cleaner->clean(
            retention: $retentionDays * 86400,
            eventRetention: $eventRetentionDays * 86400
        );

        $io->success(
            sprintf(
                'Removed %d session(s), %d password recovery link(s) and %d audit entry(-ies).',
                $removed['sessions'],
                $removed['reset_tokens'],
                $removed['events']
            )
        );

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Mail\Queue\EmailQueueInterface;
use Johncms\Mail\Queue\MailQueueSettings;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Only delivered messages are removed. The ones that were given up on stay: they are the record of
 * what never reached its recipient, and deleting them would hide exactly what needs looking at.
 */
#[AsCommand(
    name: 'mail:cleanup',
    description: 'Delete delivered messages from the mail queue',
)]
#[AsScheduledTask(expression: '40 4 * * *', withoutOverlapping: true)]
#[AsAdminTask(
    title: 'Clean up the mail queue',
    description: 'Removes messages that were delivered long ago. Messages that could not be delivered are kept.',
)]
final class CronCleanupSentEmailsCommand extends Command
{
    public function __construct(
        private readonly EmailQueueInterface $queue,
        private readonly MailQueueSettings $settings,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'retention-days',
            mode: InputOption::VALUE_REQUIRED,
            description: 'How long a delivered message is kept; defaults to the mail configuration'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $option = $input->getOption('retention-days');
        $retentionDays = $option === null ? $this->settings->keepSentDays : max(0, (int) $option);

        if ($retentionDays < 1) {
            $io->info('Delivered messages are kept forever by the current configuration; nothing to do.');

            return self::SUCCESS;
        }

        $removed = $this->queue->pruneSent($retentionDays);

        $io->success(sprintf('Removed %d delivered message(s) older than %d day(s).', $removed, $retentionDays));

        return self::SUCCESS;
    }
}

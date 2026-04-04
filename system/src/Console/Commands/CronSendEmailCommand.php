<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Mail\EmailSender;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mail:send-pending',
    description: 'Send pending emails from the queue',
)]
#[AsScheduledTask(expression: '* * * * *')]
final class CronSendEmailCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            name: 'limit',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Max number of queued emails to process',
            default: 5
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int) $input->getOption('limit');
        if ($limit < 1) {
            $output->writeln('<error>The --limit option must be greater than 0.</error>');
            return self::FAILURE;
        }

        EmailSender::send($limit);
        $output->writeln(sprintf('<info>Processed email queue with limit %d.</info>', $limit));

        return self::SUCCESS;
    }
}

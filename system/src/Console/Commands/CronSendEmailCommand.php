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
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mail:send-pending',
    description: 'Send pending emails from the queue',
)]
#[AsScheduledTask(expression: '* * * * *')]
final class CronSendEmailCommand extends Command
{
    public function __construct(private readonly EmailSender $sender)
    {
        parent::__construct();
    }

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
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');
        if ($limit < 1) {
            $io->error('The --limit option must be greater than 0.');
            return self::FAILURE;
        }

        $result = $this->sender->send($limit);

        $io->success(
            sprintf(
                'Processed %d of at most %d queued emails: %d sent, %d to be tried again, %d given up on.',
                $result->processed(),
                $limit,
                $result->sent,
                $result->retrying,
                $result->failed
            )
        );

        return self::SUCCESS;
    }
}

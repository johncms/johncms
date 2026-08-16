<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Illuminate\Database\Schema\Builder;
use Johncms\AdminTasks\AsAdminTask;
use Johncms\Mail\Schema\MailSchema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Brings the mail queue of an existing installation up to what a fresh one gets: the columns that
 * track delivery, without which a message that could not be sent is lost instead of retried.
 *
 * Deliberately safe to run again: it creates what is missing and leaves the rest alone.
 */
#[AsCommand(
    name: 'mail:upgrade-schema',
    description: 'Add the delivery tracking columns to the mail queue',
)]
#[AsAdminTask(
    title: 'Update the mail queue table',
    description: 'Adds the columns that let a failed message be tried again instead of being lost. Safe to run more than once.',
)]
final class MailUpgradeSchemaCommand extends Command
{
    public function __construct(private readonly Builder $schema)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            MailSchema::create($this->schema);
        } catch (Throwable $exception) {
            $io->error('Could not update the mail queue table: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $io->success('The mail queue table is up to date.');

        return self::SUCCESS;
    }
}

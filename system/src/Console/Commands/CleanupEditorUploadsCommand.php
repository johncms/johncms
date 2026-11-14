<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\EditorUploads\CleanupEditorUploadsUseCase;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'editor-uploads:cleanup',
    description: 'Delete the pictures of the text editor that were uploaded and never sent',
)]
#[AsScheduledTask(expression: '15 * * * *', withoutOverlapping: true)]
final class CleanupEditorUploadsCommand extends Command
{
    public function __construct(
        private readonly CleanupEditorUploadsUseCase $cleanup,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'ttl-hours',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Delete pictures older than this amount of hours',
            default: 24
        );
        $this->addOption(
            name: 'batch-size',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Maximum number of pictures to process in one pass',
            default: 500
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ttlHours = (int) $input->getOption('ttl-hours');
        $batchSize = (int) $input->getOption('batch-size');

        if ($ttlHours < 1 || $batchSize < 1) {
            $io->error('The --ttl-hours and --batch-size options must be greater than 0.');

            return self::FAILURE;
        }

        $result = $this->cleanup->execute($ttlHours, $batchSize);
        $io->success(sprintf(
            'Editor uploads cleanup finished: %d abandoned, %d untracked.',
            $result['abandoned'],
            $result['untracked']
        ));

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Console;

use Johncms\Modules\Forum\Application\UseCases\CleanupOrphanForumFilesUseCase;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'forum:cleanup-orphan-files',
    description: 'Delete orphaned forum files from storage',
)]
#[AsScheduledTask(expression: '0 * * * *', withoutOverlapping: true)]
final class CleanupOrphanFilesCommand extends Command
{
    public function __construct(
        private readonly CleanupOrphanForumFilesUseCase $cleanupOrphanForumFilesUseCase,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'ttl-hours',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Delete files older than this amount of hours',
            default: 24
        );
        $this->addOption(
            name: 'batch-size',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Maximum number of files to process in one run',
            default: 500
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ttlHours = (int) $input->getOption('ttl-hours');
        $batchSize = (int) $input->getOption('batch-size');

        if ($ttlHours < 1) {
            $io->error('The --ttl-hours option must be greater than 0.');
            return self::FAILURE;
        }

        if ($batchSize < 1) {
            $io->error('The --batch-size option must be greater than 0.');
            return self::FAILURE;
        }

        $this->cleanupOrphanForumFilesUseCase->execute($ttlHours, $batchSize);
        $io->success(
            sprintf(
                'Forum orphan files cleanup finished (ttl=%dh, batch=%d).',
                $ttlHours,
                $batchSize
            )
        );

        return self::SUCCESS;
    }
}

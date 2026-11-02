<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Console;

use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'collections:cleanup-uploads', description: 'Remove expired collection upload contexts and unreferenced uploads')]
#[AsScheduledTask(expression: '0 * * * *', withoutOverlapping: true)]
final class CleanupCollectionUploadsCommand extends Command
{
    public function __construct(private readonly CollectionItemFiles $files)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('ttl-hours', null, InputOption::VALUE_REQUIRED, 'Age of abandoned contexts in hours', 24);
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Maximum contexts per run', 500);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ttl = filter_var($input->getOption('ttl-hours'), FILTER_VALIDATE_INT);
        $limit = filter_var($input->getOption('batch-size'), FILTER_VALIDATE_INT);
        if ($ttl === false || $ttl < 1 || $limit === false || $limit < 1 || $limit > 10000) {
            $io->error('Use a positive --ttl-hours and --batch-size between 1 and 10000.');
            return self::FAILURE;
        }
        $io->success(sprintf('Removed %d expired upload contexts.', $this->files->cleanup($ttl, $limit)));
        return self::SUCCESS;
    }
}

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

#[AsCommand(name: 'forum:cleanup-orphan-files', description: 'Delete the forum attachments no post points at')]
#[AsScheduledTask(expression: '40 4 * * *', withoutOverlapping: true)]
final class CleanupOrphanForumFilesCommand extends Command
{
    public function __construct(private readonly CleanupOrphanForumFilesUseCase $cleanup)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('min-age-hours', null, InputOption::VALUE_REQUIRED, 'A file younger than this may belong to a post being saved', 24);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = filter_var($input->getOption('min-age-hours'), FILTER_VALIDATE_INT);
        if ($hours === false || $hours < 1) {
            $io->error('Use a positive --min-age-hours.');

            return self::FAILURE;
        }

        $io->success(sprintf('Deleted %d orphan attachment(s).', $this->cleanup->execute($hours * 3600)));

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Cache\CachePoolFactory;
use Johncms\Scheduler\AsScheduledTask;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Removes the cache entries that are expired or were invalidated by a tag.
 *
 * Neither an expiry nor a tag invalidation deletes anything by itself — an entry is read as a
 * miss and left on disk. Without this running, data/cache grows for as long as the site does.
 * The drivers that keep the cache in memory expire their entries themselves and need nothing
 * pruned.
 */
#[AsCommand(
    name: 'cache:pool:prune',
    description: 'Remove expired and invalidated entries from the application cache',
)]
#[AsScheduledTask(expression: '20 4 * * *', withoutOverlapping: true)]
#[AsAdminTask(
    title: 'Prune the application cache',
    description: 'Frees the space taken by cache entries that expired or were invalidated.',
)]
final class CachePoolPruneCommand extends Command
{
    public function __construct(
        private readonly CachePoolFactory $poolFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pool = $this->poolFactory->createPool();

        if (! $pool instanceof PruneableInterface) {
            $io->info('The configured cache driver expires its entries by itself; there is nothing to prune.');

            return self::SUCCESS;
        }

        if (! $pool->prune()) {
            $io->error('Failed to prune the application cache.');

            return self::FAILURE;
        }

        $io->success('The application cache is pruned.');

        return self::SUCCESS;
    }
}

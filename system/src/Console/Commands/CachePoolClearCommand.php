<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\AdminTasks\AsAdminTask;
use Johncms\Cache\CacheInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clears what the modules cached, and nothing else.
 *
 * `cache:clear` wipes the whole of data/cache — the compiled container, the routes and the
 * compiled templates included — which is what to reach for after an upgrade. This one leaves
 * all of that alone, and with tags given it drops only the entries filed under them.
 */
#[AsCommand(
    name: 'cache:pool:clear',
    description: 'Clear the application cache, optionally only the entries under the given tags',
)]
#[AsAdminTask(
    title: 'Clear the application cache',
    description: 'Removes what the modules cached. The compiled container, routes and templates are left alone.',
)]
final class CachePoolClearCommand extends Command
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'tags',
            mode: InputArgument::IS_ARRAY,
            description: 'Invalidate only the entries filed under these tags, e.g. news counters'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string[] $tags */
        $tags = $input->getArgument('tags');

        if ($tags !== []) {
            if (! $this->cache->invalidateTags(...$tags)) {
                $io->error(sprintf('Failed to invalidate the tags: %s.', implode(', ', $tags)));

                return self::FAILURE;
            }

            $io->success(sprintf('Invalidated the cache entries tagged: %s.', implode(', ', $tags)));
            // The entries are only marked stale here; `cache:pool:prune` is what frees the space.
            $io->note('The space they take is freed by the next prune run.');

            return self::SUCCESS;
        }

        if (! $this->cache->clear()) {
            $io->error('Failed to clear the application cache.');

            return self::FAILURE;
        }

        $io->success('The application cache is cleared.');

        return self::SUCCESS;
    }
}

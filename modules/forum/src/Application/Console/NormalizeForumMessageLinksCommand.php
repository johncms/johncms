<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Console;

use Illuminate\Support\Collection;
use Johncms\Console\OneTimeTaskTracker;
use Johncms\Modules\Forum\Application\Services\ForumMessageLinkNormalizer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'forum:normalize-message-links',
    description: 'One-time: unwrap internal /redirect/ links in forum posts and rewrite legacy show_post links to /forum/post/{id}/',
)]
final class NormalizeForumMessageLinksCommand extends Command
{
    public function __construct(
        private readonly ForumMessageLinkNormalizer $normalizer,
        private readonly ForumTopicPathService $topicPathService,
        private readonly OneTimeTaskTracker $tracker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'domain',
            mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            description: 'Host(s) treated as internal. Defaults to the host of johncms.homeurl.'
        );
        $this->addOption(
            name: 'batch-size',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Maximum number of messages to process per chunk',
            default: 500
        );
        $this->addOption(
            name: 'dry-run',
            mode: InputOption::VALUE_NONE,
            description: 'Show how many messages would change without writing changes'
        );
        $this->addOption(
            name: 'force',
            mode: InputOption::VALUE_NONE,
            description: 'Run again even if this one-time task has already been completed'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = (int) $input->getOption('batch-size');
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');

        if ($batchSize < 1) {
            $io->error('The --batch-size option must be greater than 0.');
            return self::FAILURE;
        }

        $hosts = $this->resolveHosts($input->getOption('domain'));
        if ($hosts === []) {
            $io->error('No internal host resolved. Pass --domain=example.com or set johncms.homeurl.');
            return self::FAILURE;
        }

        if ($this->tracker->isCompleted($this->getName()) && ! $force) {
            $io->warning('This one-time task has already been completed. Use --force to run it again.');
            return self::SUCCESS;
        }

        $io->text('Internal hosts: ' . implode(', ', $hosts));

        $topicUrlResolver = $this->makeTopicUrlResolver();

        $changed = 0;
        $processed = 0;

        ForumMessage::query()
            ->orderBy('id')
            ->chunkById($batchSize, function (Collection $messages) use (&$changed, &$processed, $hosts, $topicUrlResolver, $dryRun): void {
                foreach ($messages as $message) {
                    $processed++;
                    $original = (string) $message->getRawOriginal('text');
                    $normalized = $this->normalizer->normalize($original, $hosts, $topicUrlResolver);
                    if ($normalized === $original) {
                        continue;
                    }

                    $changed++;
                    if (! $dryRun) {
                        $message->text = $normalized;
                        $message->save();
                    }
                }
            });

        if (! $dryRun) {
            $this->tracker->markCompleted($this->getName());
        }

        $io->success(
            sprintf(
                '%s: processed %d messages, %d %s updated.',
                $dryRun ? 'Dry run' : 'Done',
                $processed,
                $changed,
                $dryRun ? 'would be' : 'were'
            )
        );

        return self::SUCCESS;
    }

    /**
     * @return callable(int $topicId, ?int $page): ?string
     */
    private function makeTopicUrlResolver(): callable
    {
        $cache = [];

        return function (int $topicId, ?int $page) use (&$cache): ?string {
            $base = $cache[$topicId] ??= $this->topicPathService->getTopicUrlById($topicId);
            if ($base === null) {
                return null;
            }

            return $page !== null && $page > 1 ? $base . '?page=' . $page : $base;
        };
    }

    /**
     * @param string[] $domains
     * @return string[]
     */
    private function resolveHosts(array $domains): array
    {
        if ($domains !== []) {
            return $domains;
        }

        $host = parse_url((string) config('johncms.homeurl'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? [$host] : [];
    }
}

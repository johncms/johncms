<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Carbon\Carbon;
use Johncms\Files\FileStore;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageFileRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class CleanupOrphanForumFilesUseCase
{
    public function __construct(
        private FileStore $files,
        private ForumMessageFileRepositoryInterface $messageFileRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(int $ttlHours = 24, int $batchSize = 500): void
    {
        $batchSize = max(1, $batchSize);

        $threshold = Carbon::now()->subHours($ttlHours)->format('Y-m-d H:i:s');
        $fileIds = $this->messageFileRepository->getOrphanStorageFileIds('forum_files', $threshold, $batchSize);

        if ($fileIds === []) {
            return;
        }

        // A file whose disk refuses to give it up is logged by the store and its row is gone
        // either way, so there is nothing left here to count as a failure.
        $this->files->deleteMany($fileIds);

        $this->logger->info(
            '[ForumFilesCleanup] Cleanup completed',
            [
                'candidates' => count($fileIds),
                'ttl_hours'  => $ttlHours,
            ]
        );
    }
}

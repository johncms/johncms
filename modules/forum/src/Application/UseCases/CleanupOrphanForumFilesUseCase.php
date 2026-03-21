<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Carbon\Carbon;
use Johncms\Files\FileStorage;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageFileRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class CleanupOrphanForumFilesUseCase
{
    public function __construct(
        private FileStorage $fileStorage,
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

        $deleted = 0;
        $failed = 0;

        foreach ($fileIds as $fileId) {
            try {
                $this->fileStorage->delete($fileId);
                ++$deleted;
            } catch (Throwable $exception) {
                ++$failed;

                $this->logger->error(
                    '[ForumFilesCleanup] Failed to delete orphan forum file',
                    [
                        'file_id' => $fileId,
                        'error'   => $exception->getMessage(),
                    ]
                );
            }
        }

        $this->logger->info(
            '[ForumFilesCleanup] Cleanup completed',
            [
                'candidates' => count($fileIds),
                'deleted'    => $deleted,
                'failed'     => $failed,
                'ttl_hours'  => $ttlHours,
            ]
        );
    }
}

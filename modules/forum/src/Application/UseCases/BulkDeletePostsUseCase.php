<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

final readonly class BulkDeletePostsUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicStatsRecalculator $topicStatsRecalculator,
    ) {
    }

    /**
     * @param int[] $ids
     */
    public function execute(int $topicId, array $ids, string $deletedBy): void
    {
        $existingIds = $this->messageRepository->getExistingIdsByTopic($topicId, $ids);
        if ($existingIds === []) {
            return;
        }

        $this->messageRepository->markDeletedByIds($existingIds, $deletedBy);
        $this->topicStatsRecalculator->recalculate($topicId);
    }
}

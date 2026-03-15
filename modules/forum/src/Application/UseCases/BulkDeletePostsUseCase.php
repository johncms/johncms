<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\System\Legacy\Tools;

final readonly class BulkDeletePostsUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private Tools $tools,
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
        $this->tools->recountForumTopic($topicId);
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\BulkDeletePostsResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

/**
 * Hides the chosen posts of a topic. A topic is never left in sight without a post to show: when
 * the choice takes every post still in sight, the topic is hidden instead and its posts stay as
 * they are — the same as hiding the last post of a topic one by one does. The files attached go out
 * of sight with what was hidden, as they do when a post or a topic is deleted on its own.
 */
final readonly class BulkDeletePostsUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumFileRepositoryInterface $fileRepository,
        private ForumTopicStatsRecalculator $topicStatsRecalculator,
        private ForumSectionPathService $sectionPathService,
    ) {
    }

    /**
     * @param int[] $ids
     */
    public function execute(int $topicId, array $ids, string $deletedBy): BulkDeletePostsResultDTO
    {
        $visibleIds = $this->messageRepository->getVisibleIdsByTopic($topicId, $ids);
        if ($visibleIds === []) {
            return new BulkDeletePostsResultDTO(topicHidden: false, sectionUrl: '');
        }

        if (count($visibleIds) >= $this->messageRepository->countByTopicId($topicId, false)) {
            $topic = $this->topicRepository->findById($topicId);
            $this->topicRepository->markDeleted($topicId, $deletedBy);
            $this->fileRepository->markDeletedByTopicId($topicId);

            return new BulkDeletePostsResultDTO(
                topicHidden: true,
                sectionUrl: ($topic !== null ? $this->sectionPathService->getSectionUrlById($topic->section_id) : null) ?? '/forum/',
            );
        }

        $this->messageRepository->markDeletedByIds($visibleIds, $deletedBy);
        $this->fileRepository->markDeletedByPostIds($visibleIds);
        $this->topicStatsRecalculator->recalculate($topicId);

        return new BulkDeletePostsResultDTO(topicHidden: false, sectionUrl: '');
    }
}

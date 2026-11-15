<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\DTO\TopicsPeriodQueryDTO;
use Johncms\Modules\Forum\Application\DTO\TopicsPeriodResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicRows;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class ViewTopicsByPeriodUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicRows $topicRows,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(TopicsPeriodQueryDTO $query): TopicsPeriodResultDTO
    {
        // The moderation figures and the deleted topics go together, and both are what
        // "may see what was deleted" stands for.
        $useModerationDate = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
        $includeDeleted = $useModerationDate;
        $fromTime = time() - $query->hours * 3600;
        $total = $this->topicRepository->countForPeriod($fromTime, $includeDeleted, $useModerationDate);
        $topics = [];

        if ($total > 0) {
            $topics = $this->topicRows->present(
                rows: $this->topicRepository->getForPeriod(
                    fromTime: $fromTime,
                    includeDeleted: $includeDeleted,
                    useModerationDate: $useModerationDate,
                    start: $query->start,
                    limit: (int) $this->currentUser->user()->config->kmess,
                ),
                withPlace: true,
            );
        }

        return new TopicsPeriodResultDTO($topics, $total, $query->hours);
    }
}

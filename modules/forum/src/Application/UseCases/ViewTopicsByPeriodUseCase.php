<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\DTO\TopicsPeriodQueryDTO;
use Johncms\Modules\Forum\Application\DTO\TopicsPeriodResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ViewTopicsByPeriodUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private DateFormatterInterface $dateFormatter,
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
            $topics = $this->mapTopics(
                $this->topicRepository->getForPeriod(
                    fromTime: $fromTime,
                    includeDeleted: $includeDeleted,
                    useModerationDate: $useModerationDate,
                    start: $query->start,
                    limit: (int) $this->currentUser->user()->config->kmess,
                )
            );
        }

        return new TopicsPeriodResultDTO($topics, $total, $query->hours);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapTopics(array $rows): array
    {
        $topics = [];

        foreach ($rows as $row) {
            if ($this->accessChecker->allows(ForumPermissions::DELETED_VIEW)) {
                $pagesCount = (int) ceil((int) $row['mod_post_count'] / $this->currentUser->user()->config->kmess);
                $row['show_posts_count'] = ShortNumberFormatter::format((int) $row['mod_post_count']);
                $row['show_last_author'] = $row['mod_last_post_author_name'];
                $row['show_last_post_date'] = $this->dateFormatter->format((int) $row['mod_last_post_date']);
            } else {
                $pagesCount = (int) ceil((int) $row['post_count'] / $this->currentUser->user()->config->kmess);
                $row['show_posts_count'] = ShortNumberFormatter::format((int) $row['post_count']);
                $row['show_last_author'] = $row['last_post_author_name'];
                $row['show_last_post_date'] = $this->dateFormatter->format((int) $row['last_post_date']);
            }

            $row['has_icons'] = ! empty($row['pinned']) || ! empty($row['has_poll']) || ! empty($row['closed']) || ! empty($row['deleted']);
            $row['url'] = $this->topicPathService->getTopicUrlById((int) $row['id']) ?? '/forum/';
            $row['last_page_url'] = $row['url'];
            if ($pagesCount > 1) {
                $row['last_page_url'] = $this->topicPathService->getTopicUrlById((int) $row['id'], $pagesCount) ?? $row['url'];
            }

            $row['forum_url'] = '';
            $row['section_url'] = '';
            $topics[] = $row;
        }

        return $topics;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\DTO\UnreadTopicsQueryDTO;
use Johncms\Modules\Forum\Application\DTO\UnreadTopicsResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ViewUnreadTopicsUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumSectionPathService $sectionPathService,
        private ForumTopicPathService $topicPathService,
        private DateFormatterInterface $dateFormatter,
        private User $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(UnreadTopicsQueryDTO $query): UnreadTopicsResultDTO
    {
        $includeDeleted = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
        $total = $this->topicRepository->countUnreadForUser((int) $this->currentUser->id, $includeDeleted);
        $topics = [];

        if ($total > 0) {
            $topics = $this->mapTopics(
                $this->topicRepository->getUnreadForUser(
                    userId: (int) $this->currentUser->id,
                    includeDeleted: $includeDeleted,
                    start: $query->start,
                    limit: (int) $this->currentUser->config->kmess,
                )
            );
        }

        return new UnreadTopicsResultDTO($topics, $total);
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
                $pagesCount = (int) ceil((int) $row['mod_post_count'] / $this->currentUser->config->kmess);
                $row['show_posts_count'] = ShortNumberFormatter::format((int) $row['mod_post_count']);
                $row['show_last_author'] = $row['mod_last_post_author_name'];
                $row['show_last_post_date'] = $this->dateFormatter->format((int) $row['mod_last_post_date']);
            } else {
                $pagesCount = (int) ceil((int) $row['post_count'] / $this->currentUser->config->kmess);
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
            if (! empty($row['frm_id'])) {
                $row['forum_url'] = $this->sectionPathService->getSectionUrlById((int) $row['frm_id']) ?? '';
            }

            $row['section_url'] = '';
            if (! empty($row['section_id'])) {
                $row['section_url'] = $this->sectionPathService->getSectionUrlById((int) $row['section_id']) ?? '';
            }

            $topics[] = $row;
        }

        return $topics;
    }
}

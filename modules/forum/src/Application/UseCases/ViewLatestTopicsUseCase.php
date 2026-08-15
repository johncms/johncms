<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\DTO\LatestTopicsResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ViewLatestTopicsUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private DateFormatterInterface $dateFormatter,
        private User $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(): LatestTopicsResultDTO
    {
        $topics = $this->mapTopics($this->topicRepository->getLatest(10));

        return new LatestTopicsResultDTO($topics, count($topics));
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
            $row['section_url'] = '';
            $topics[] = $row;
        }

        return $topics;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\ForumTopicRowDTO;
use Johncms\Utils\DateFormatterInterface;

/**
 * The topics of a listing as rows: the section, the unread ones, a period, the latest.
 *
 * Takes the columns of forum_topic as they are in the table, so the rows of a query and the
 * attributes of a model serve alike. The counts are those the visitor may see: with
 * forum.deleted.view the hidden posts count too. Addresses come from ForumSectionPaths, without a
 * query for each row; the columns may be empty in topics never recounted.
 */
final readonly class ForumTopicRows
{
    public function __construct(
        private ForumSectionPaths $sectionPaths,
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * @param iterable<array<string, mixed>> $rows
     * @param bool                           $withPlace Name the section and the category of each topic.
     * @param bool                           $allUnread Every topic is unread: the listing is of the unread ones.
     * @return list<ForumTopicRowDTO>
     */
    public function present(iterable $rows, bool $withPlace = false, bool $allUnread = false): array
    {
        $withHidden = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
        $perPage = max(1, (int) $this->currentUser->user()->config->kmess);
        $member = $this->currentUser->isValid();

        $topics = [];
        foreach ($rows as $row) {
            $posts = (int) ($withHidden ? $row['mod_post_count'] : $row['post_count']);
            $lastDate = (int) ($withHidden ? $row['mod_last_post_date'] : $row['last_post_date']);
            $sectionId = (int) $row['section_id'];
            $url = $this->url($row, $sectionId);
            $pages = (int) ceil($posts / $perPage);
            $section = $withPlace ? $this->sectionPaths->find($sectionId) : null;
            $category = $section !== null && (int) $section->parent > 0 ? $this->sectionPaths->find((int) $section->parent) : null;

            $topics[] = new ForumTopicRowDTO(
                id: (int) $row['id'],
                name: (string) $row['name'],
                url: $url,
                lastPageUrl: $pages > 1 ? $url . '?page=' . $pages : $url,
                posts: $posts,
                author: (string) $row['user_name'],
                lastAuthor: (string) ($withHidden ? $row['mod_last_post_author_name'] : $row['last_post_author_name']),
                lastDate: $lastDate > 0 ? $this->dateFormatter->format($lastDate) : '',
                pinned: (bool) $row['pinned'],
                closed: (bool) $row['closed'],
                deleted: (bool) $row['deleted'],
                deletedBy: (string) $row['deleted_by'],
                hasPoll: (bool) $row['has_poll'],
                unread: $allUnread || ($member && array_key_exists('read', $row) && (int) $row['read'] === 0),
                sectionName: $section !== null ? (string) $section->name : '',
                sectionUrl: $section !== null ? (string) $this->sectionPaths->url($section->id) : '',
                categoryName: $category !== null ? (string) $category->name : '',
                categoryUrl: $category !== null ? (string) $this->sectionPaths->url($category->id) : '',
            );
        }

        return $topics;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function url(array $row, int $sectionId): string
    {
        $sectionUrl = $this->sectionPaths->url($sectionId);
        if ($sectionUrl === null) {
            return '/forum/';
        }

        $slug = trim((string) $row['slug']);

        return $sectionUrl . ($slug !== '' ? $slug : 'topic-' . $row['id']) . '-' . $row['id'] . '/';
    }
}

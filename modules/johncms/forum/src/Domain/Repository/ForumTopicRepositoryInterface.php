<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;

interface ForumTopicRepositoryInterface
{
    public function findById(int $topicId): ?ForumTopic;

    public function findByIdWithSection(int $topicId, bool $withFilesCount): ?ForumTopic;

    public function existsBySectionAndSlug(int $sectionId, string $slug, ?int $excludeTopicId = null): bool;

    public function findActiveById(int $topicId): ?ForumTopic;

    public function countUnreadForUser(int $userId, bool $includeDeleted): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUnreadForUser(int $userId, bool $includeDeleted, int $start, int $limit): array;

    public function countForPeriod(int $fromTime, bool $includeDeleted, bool $useModerationDate): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getForPeriod(int $fromTime, bool $includeDeleted, bool $useModerationDate, int $start, int $limit): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLatest(int $limit, bool $onlyPublished = false, ?int $readerId = null): array;

    public function countReadBySectionId(int $sectionId): int;

    /**
     * Topics, posts and the date of the last post of every section that holds topics. With
     * $withHidden the hidden topics and posts count as well, and the dates are those of the staff.
     *
     * @return list<array{section_id: int, topics: int, posts: int, last_date: int}>
     */
    public function getSectionStats(bool $withHidden): array;

    /**
     * The topics whose last post is the given date in the given section, for each pair given. A
     * section may answer with more than one when two topics end at the same second.
     *
     * @param array<int, int> $lastDates Section id => date of its last post.
     * @return Collection<int, ForumTopic>
     */
    public function getEndingAt(array $lastDates, bool $withHidden): Collection;

    /**
     * @return Collection<int, ForumTopic>
     */
    public function getReadBySectionId(int $sectionId, int $limit, int $offset): Collection;

    public function markHasPoll(int $topicId): void;

    public function clearHasPoll(int $topicId): void;

    public function setClosed(int $topicId, bool $closed): void;

    public function markDeleted(int $topicId, string $deletedBy): void;

    public function deleteById(int $topicId): void;

    public function setPinned(int $topicId, bool $pinned): void;

    public function restoreById(int $topicId, string $restoredBy): void;

    public function save(ForumTopic $topic): void;

    public function incrementViewCount(int $topicId): void;

    /**
     * Writes the recalculated denormalized topic counters and last post data.
     *
     * @param array<string, mixed> $stats
     */
    public function updateStats(int $topicId, array $stats): void;

    /**
     * @return iterable<int, ForumTopic>
     */
    public function getCursorForSitemap(): iterable;
}

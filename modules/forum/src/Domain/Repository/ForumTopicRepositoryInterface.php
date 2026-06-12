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
    public function getLatest(int $limit): array;

    public function countReadBySectionId(int $sectionId): int;

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
     * @return iterable<int, ForumTopic>
     */
    public function getCursorForSitemap(): iterable;
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;

interface ForumMessageRepositoryInterface
{
    public function findById(int $id): ?ForumMessage;

    /** The raw text of the post, hidden or not, its row held until the transaction ends; null when it is gone. */
    public function findTextLocked(int $id): ?string;

    /** Whether the post, hidden or not, is still there; its row is held until the transaction ends. */
    public function lockExisting(int $id): bool;

    /**
     * The posts answers recall, hidden or not, by id: who wrote them, their text and whether they are hidden.
     *
     * @param list<int> $ids
     * @return array<int, ForumMessage>
     */
    public function getAnsweredByIds(array $ids): array;

    /**
     * Posts of the topics, hidden ones included.
     *
     * @param list<int> $topicIds
     * @return list<int>
     */
    public function getIdsByTopicIds(array $topicIds): array;

    public function deleteByTopicId(int $topicId): void;

    public function save(ForumMessage $message): void;

    public function findLastMessageByUser(int $userId): ?ForumMessage;

    public function findLastMessageInTopic(int $topicId, bool $includeDeleted): ?ForumMessage;

    public function findLastByTopicId(int $topicId, bool $includeDeleted): ?ForumMessage;

    public function findFirstByTopicId(int $topicId): ?ForumMessage;

    /**
     * Count all messages of a topic (including deleted), optionally filtered by author.
     *
     * @param int[] $filterUserIds
     */
    public function countAllByTopicId(int $topicId, array $filterUserIds = []): int;

    /**
     * @param int[] $filterUserIds
     * @return Collection<int, ForumMessage>
     */
    public function getByTopicIdWithUsersAndFiles(
        int $topicId,
        bool $upfp,
        int $limit,
        int $offset,
        array $filterUserIds = [],
    ): Collection;

    public function countByTopicId(int $topicId, bool $includeDeleted): int;

    public function countByTopicIdWithComparison(
        int $topicId,
        int $messageId,
        bool $upfp,
        bool $includeDeleted,
        bool $strict,
    ): int;

    public function markDeletedById(int $messageId, string $deletedBy): void;

    public function restoreById(int $messageId, string $restoredBy): void;

    public function deleteById(int $messageId): void;

    /**
     * The ids among the given ones of the posts of the topic that are not hidden.
     *
     * @param int[] $ids
     * @return int[]
     */
    public function getVisibleIdsByTopic(int $topicId, array $ids): array;

    /**
     * @param int[] $ids
     */
    public function markDeletedByIds(array $ids, string $deletedBy): void;

    /**
     * @return array<array{user_id:int, user_name:string}>
     */
    public function getTopicCuratorCandidates(int $topicId): array;

    /**
     * @return array<int, array{user_id:int, user_name:string, count:int}>
     */
    public function getTopicAuthorFilterOptions(int $topicId): array;

    /**
     * The posts written on each of the days starting at the given second.
     *
     * @return list<int> One number per day, oldest first.
     */
    public function countPostedByDay(int $start, int $days): array;
}

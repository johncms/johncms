<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;

interface ForumMessageRepositoryInterface
{
    public function findById(int $id): ?ForumMessage;

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

    public function findFirstByTopicIdWithUsers(int $topicId): ?ForumMessage;

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
     * @param int[] $ids
     * @return int[]
     */
    public function getExistingIdsByTopic(int $topicId, array $ids): array;

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
}

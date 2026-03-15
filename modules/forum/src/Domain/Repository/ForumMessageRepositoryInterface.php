<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumMessage;

interface ForumMessageRepositoryInterface
{
    public function findById(int $id): ?ForumMessage;

    public function deleteByTopicId(int $topicId): void;

    public function save(ForumMessage $message): void;

    public function findLastMessageByUser(int $userId): ?ForumMessage;

    public function findLastMessageInTopic(int $topicId, bool $includeDeleted): ?ForumMessage;

    public function countByTopicId(int $topicId, bool $includeDeleted): int;

    /**
     * @return array<array{user_id:int, user_name:string}>
     */
    public function getTopicCuratorCandidates(int $topicId): array;
}

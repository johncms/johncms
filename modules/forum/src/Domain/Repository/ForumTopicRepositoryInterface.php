<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;

interface ForumTopicRepositoryInterface
{
    public function findById(int $topicId): ?ForumTopic;

    public function findActiveById(int $topicId): ?ForumTopic;

    public function markHasPoll(int $topicId): void;

    public function clearHasPoll(int $topicId): void;

    public function setClosed(int $topicId, bool $closed): void;

    public function markDeleted(int $topicId, string $deletedBy): void;

    public function deleteById(int $topicId): void;

    public function setPinned(int $topicId, bool $pinned): void;

    public function restoreById(int $topicId, string $restoredBy): void;

    public function save(ForumTopic $topic): void;
}

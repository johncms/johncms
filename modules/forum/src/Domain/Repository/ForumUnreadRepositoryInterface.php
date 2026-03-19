<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

interface ForumUnreadRepositoryInterface
{
    public function deleteByTopicId(int $topicId): void;

    public function markTopicAsRead(int $topicId, int $userId, int $time): void;

    public function markAllAsRead(int $userId): void;
}

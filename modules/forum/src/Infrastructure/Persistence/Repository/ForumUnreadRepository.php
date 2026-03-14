<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumUnread;
use Johncms\Modules\Forum\Domain\Repository\ForumUnreadRepositoryInterface;

final class ForumUnreadRepository implements ForumUnreadRepositoryInterface
{
    public function deleteByTopicId(int $topicId): void
    {
        ForumUnread::query()
            ->where('topic_id', $topicId)
            ->delete();
    }
}

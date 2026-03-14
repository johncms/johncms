<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

class ForumMessageRepository implements ForumMessageRepositoryInterface
{
    public function findById(int $id): ?ForumMessage
    {
        return ForumMessage::query()
            ->users()
            ->with(['files', 'topic'])
            ->find($id);
    }

    public function deleteByTopicId(int $topicId): void
    {
        ForumMessage::query()
            ->where('topic_id', $topicId)
            ->delete();
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final class ForumTopicRepository implements ForumTopicRepositoryInterface
{
    public function findActiveById(int $topicId): ?ForumTopic
    {
        return ForumTopic::query()
            ->where('id', $topicId)
            ->where(static function ($query): void {
                $query->whereNull('deleted')
                    ->orWhere('deleted', '!=', 1);
            })
            ->first();
    }

    public function markHasPoll(int $topicId): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['has_poll' => 1]);
    }

    public function clearHasPoll(int $topicId): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['has_poll' => null]);
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final class ForumTopicRepository implements ForumTopicRepositoryInterface
{
    public function findById(int $topicId): ?ForumTopic
    {
        return ForumTopic::query()->find($topicId);
    }

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

    public function setClosed(int $topicId, bool $closed): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['closed' => $closed]);
    }

    public function markDeleted(int $topicId, string $deletedBy): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['deleted' => true, 'deleted_by' => $deletedBy]);
    }

    public function deleteById(int $topicId): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->delete();
    }
}

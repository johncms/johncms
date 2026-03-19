<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
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

    public function markTopicAsRead(int $topicId, int $userId, int $time): void
    {
        ForumUnread::query()
            ->updateOrInsert(
                ['topic_id' => $topicId, 'user_id' => $userId],
                ['time' => $time]
            );
    }

    public function markAllAsRead(int $userId): void
    {
        $topics = ForumTopic::query()
            ->from('forum_topic as tpc')
            ->leftJoin('cms_forum_rdm as rdm', static function ($join) use ($userId): void {
                $join->on('tpc.id', '=', 'rdm.topic_id')
                    ->where('rdm.user_id', '=', $userId);
            })
            ->select([
                'tpc.id as topic_id',
                'tpc.last_post_date',
            ])
            ->where(static function ($query): void {
                $query->whereNull('rdm.topic_id')
                    ->orWhereColumn('tpc.last_post_date', '>', 'rdm.time');
            })
            ->toBase()
            ->get();

        if ($topics->isEmpty()) {
            return;
        }

        $values = [];
        foreach ($topics as $topic) {
            $values[] = [
                'topic_id' => (int) $topic->topic_id,
                'user_id'  => $userId,
                'time'     => (int) $topic->last_post_date,
            ];
        }

        ForumUnread::query()->upsert($values, ['topic_id', 'user_id'], ['time']);
    }
}

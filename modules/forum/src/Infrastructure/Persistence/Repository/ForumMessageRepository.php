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

    public function save(ForumMessage $message): void
    {
        $message->save();
    }

    public function findLastMessageByUser(int $userId): ?ForumMessage
    {
        return ForumMessage::query()
            ->where('user_id', $userId)
            ->orderByDesc('date')
            ->first();
    }

    public function findLastMessageInTopic(int $topicId, bool $includeDeleted): ?ForumMessage
    {
        $query = ForumMessage::query()
            ->where('topic_id', $topicId);

        if (! $includeDeleted) {
            $query->where(static function ($query): void {
                $query->whereNull('deleted')
                    ->orWhere('deleted', '!=', 1);
            });
        }

        return $query
            ->orderByDesc('date')
            ->first();
    }

    public function countByTopicId(int $topicId, bool $includeDeleted): int
    {
        $query = ForumMessage::query()
            ->where('topic_id', $topicId);

        if (! $includeDeleted) {
            $query->where(static function ($query): void {
                $query->whereNull('deleted')
                    ->orWhere('deleted', '!=', 1);
            });
        }

        return (int) $query->count();
    }

    public function getTopicCuratorCandidates(int $topicId): array
    {
        $rows = ForumMessage::query()
            ->select('forum_messages.user_id', 'forum_messages.user_name')
            ->join('users', 'users.id', '=', 'forum_messages.user_id')
            ->where('forum_messages.topic_id', $topicId)
            ->where('users.rights', '<', 6)
            ->where('users.rights', '!=', 3)
            ->groupBy('forum_messages.user_id', 'forum_messages.user_name')
            ->orderBy('forum_messages.user_name')
            ->get();

        $candidates = [];
        foreach ($rows as $row) {
            $candidates[] = [
                'user_id'   => (int) $row->user_id,
                'user_name' => (string) $row->user_name,
            ];
        }

        return $candidates;
    }
}

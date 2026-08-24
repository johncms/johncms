<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Profile\Domain\Repository\ProfileActivityRepositoryInterface;

final class EloquentProfileActivityRepository implements ProfileActivityRepositoryInterface
{
    public function countForumMessages(int $userId, bool $includeDeleted): int
    {
        return $this->forumMessagesQuery($userId, $includeDeleted)->count();
    }

    /**
     * @return Collection<int, ForumMessage>
     */
    public function getForumMessages(int $userId, bool $includeDeleted, int $limit, int $offset): Collection
    {
        return $this->forumMessagesQuery($userId, $includeDeleted)
            ->with('topic.section.parentSection')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countForumTopics(int $userId, bool $includeDeleted): int
    {
        return $this->forumTopicsQuery($userId, $includeDeleted)->count();
    }

    /**
     * @return Collection<int, ForumTopic>
     */
    public function getForumTopics(int $userId, bool $includeDeleted, int $limit, int $offset): Collection
    {
        return $this->forumTopicsQuery($userId, $includeDeleted)
            ->with('section.parentSection')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function findFirstTopicMessage(int $topicId, bool $includeDeleted): ?ForumMessage
    {
        $query = ForumMessage::query()
            ->where('topic_id', '=', $topicId)
            ->orderBy('id');

        $this->applyNotDeleted($query, $includeDeleted);

        return $query->first();
    }

    public function countGuestbookEntries(int $userId, bool $includeAdmin): int
    {
        return $this->guestbookEntriesQuery($userId, $includeAdmin)->count();
    }

    /**
     * @return Collection<int, GuestbookEntry>
     */
    public function getGuestbookEntries(int $userId, bool $includeAdmin, int $limit, int $offset): Collection
    {
        return $this->guestbookEntriesQuery($userId, $includeAdmin)
            ->with('user')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<ForumMessage>
     */
    private function forumMessagesQuery(int $userId, bool $includeDeleted): Builder
    {
        $query = ForumMessage::query()->where('user_id', '=', $userId);
        $this->applyNotDeleted($query, $includeDeleted);

        return $query;
    }

    /**
     * @return Builder<ForumTopic>
     */
    private function forumTopicsQuery(int $userId, bool $includeDeleted): Builder
    {
        $query = ForumTopic::query()->where('user_id', '=', $userId);
        $this->applyNotDeleted($query, $includeDeleted);

        return $query;
    }

    /**
     * @return Builder<GuestbookEntry>
     */
    private function guestbookEntriesQuery(int $userId, bool $includeAdmin): Builder
    {
        $query = GuestbookEntry::query()->where('user_id', '=', $userId);

        if (! $includeAdmin) {
            $query->where('adm', '=', 0);
        }

        return $query;
    }

    private function applyNotDeleted(Builder $query, bool $includeDeleted): void
    {
        if ($includeDeleted) {
            return;
        }

        $query->where(static function (Builder $q): void {
            $q->where('deleted', '!=', '1')->orWhereNull('deleted');
        });
    }
}

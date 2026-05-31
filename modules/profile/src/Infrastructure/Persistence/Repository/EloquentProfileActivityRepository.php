<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Profile\Domain\Repository\ProfileActivityRepositoryInterface;

final class EloquentProfileActivityRepository implements ProfileActivityRepositoryInterface
{
    public function paginateForumMessages(int $userId, bool $includeDeleted, int $perPage): LengthAwarePaginator
    {
        $query = ForumMessage::query()
            ->where('user_id', '=', $userId)
            ->with('topic.section.parentSection')
            ->orderByDesc('id');

        $this->applyNotDeleted($query, $includeDeleted);

        return $query->paginate($perPage);
    }

    public function paginateForumTopics(int $userId, bool $includeDeleted, int $perPage): LengthAwarePaginator
    {
        $query = ForumTopic::query()
            ->where('user_id', '=', $userId)
            ->with('section.parentSection')
            ->orderByDesc('id');

        $this->applyNotDeleted($query, $includeDeleted);

        return $query->paginate($perPage);
    }

    public function findFirstTopicMessage(int $topicId, bool $includeDeleted): ?ForumMessage
    {
        $query = ForumMessage::query()
            ->where('topic_id', '=', $topicId)
            ->orderBy('id');

        $this->applyNotDeleted($query, $includeDeleted);

        return $query->first();
    }

    public function paginateGuestbookEntries(int $userId, bool $includeAdmin, int $perPage): LengthAwarePaginator
    {
        $query = GuestbookEntry::query()
            ->where('user_id', '=', $userId)
            ->with('user')
            ->orderByDesc('id');

        if (! $includeAdmin) {
            $query->where('adm', '=', 0);
        }

        return $query->paginate($perPage);
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

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Auth\AuthTables;
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

    public function findLastByTopicId(int $topicId, bool $includeDeleted): ?ForumMessage
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
            ->orderByDesc('id')
            ->first();
    }

    public function findFirstByTopicId(int $topicId): ?ForumMessage
    {
        return ForumMessage::query()
            ->where('topic_id', $topicId)
            ->orderBy('id')
            ->first();
    }

    public function countAllByTopicId(int $topicId, array $filterUserIds = []): int
    {
        $query = ForumMessage::query()->where('topic_id', $topicId);

        if ($filterUserIds !== []) {
            $query->whereIn('user_id', $filterUserIds);
        }

        return $query->count();
    }

    /**
     * @return Collection<int, ForumMessage>
     */
    public function getByTopicIdWithUsersAndFiles(
        int $topicId,
        bool $upfp,
        int $limit,
        int $offset,
        array $filterUserIds = [],
    ): Collection {
        $query = ForumMessage::query()
            ->users()
            ->with('files')
            ->where('topic_id', $topicId);

        if ($filterUserIds !== []) {
            $query->whereIn('user_id', $filterUserIds);
        }

        return $query
            ->orderBy('id', $upfp ? 'DESC' : 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function findFirstByTopicIdWithUsers(int $topicId): ?ForumMessage
    {
        return ForumMessage::query()
            ->users()
            ->where('topic_id', $topicId)
            ->orderBy('id')
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

    public function countByTopicIdWithComparison(
        int $topicId,
        int $messageId,
        bool $upfp,
        bool $includeDeleted,
        bool $strict,
    ): int {
        $query = ForumMessage::query()
            ->where('topic_id', $topicId)
            ->where('id', $upfp ? ($strict ? '>' : '>=') : ($strict ? '<' : '<='), $messageId);

        if (! $includeDeleted) {
            $query->where(static function ($query): void {
                $query->whereNull('deleted')
                    ->orWhere('deleted', '!=', 1);
            });
        }

        return (int) $query->count();
    }

    public function markDeletedById(int $messageId, string $deletedBy): void
    {
        ForumMessage::query()
            ->where('id', $messageId)
            ->update(['deleted' => 1, 'deleted_by' => $deletedBy]);
    }

    public function restoreById(int $messageId, string $restoredBy): void
    {
        ForumMessage::query()
            ->where('id', $messageId)
            ->update(['deleted' => null, 'deleted_by' => $restoredBy]);
    }

    public function deleteById(int $messageId): void
    {
        ForumMessage::query()
            ->where('id', $messageId)
            ->delete();
    }

    public function getExistingIdsByTopic(int $topicId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return ForumMessage::query()
            ->where('topic_id', $topicId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function markDeletedByIds(array $ids, string $deletedBy): void
    {
        if ($ids === []) {
            return;
        }

        ForumMessage::query()
            ->whereIn('id', $ids)
            ->update(['deleted' => 1, 'deleted_by' => $deletedBy]);
    }

    public function getTopicCuratorCandidates(int $topicId): array
    {
        $rows = ForumMessage::query()
            ->select('forum_messages.user_id', 'forum_messages.user_name')
            ->join('users', 'users.id', '=', 'forum_messages.user_id')
            ->where('forum_messages.topic_id', $topicId)
            // A curator is appointed among the members of the topic. Whoever already holds a
            // role of the staff moderates it without being appointed to anything.
            ->whereNotExists($this->grantedRoles()->toBase())
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

    /**
     * The roles granted to the account the outer query is looking at, still in force.
     *
     * @return Builder<UserRole>
     */
    private function grantedRoles(): Builder
    {
        $now = time();

        /** @var Builder<UserRole> $query */
        $query = UserRole::query();
        $query->whereColumn(AuthTables::USER_ROLES . '.user_id', 'users.id');
        $query->where(
            static function (Builder $builder) use ($now): void {
                $builder->whereNull(AuthTables::USER_ROLES . '.expires_at')
                    ->orWhere(AuthTables::USER_ROLES . '.expires_at', '>', $now);
            }
        );

        return $query;
    }

    public function getTopicAuthorFilterOptions(int $topicId): array
    {
        $rows = ForumMessage::query()
            ->selectRaw('forum_messages.user_id, forum_messages.user_name, COUNT(*) as count')
            ->where('forum_messages.topic_id', $topicId)
            ->groupBy('forum_messages.user_id', 'forum_messages.user_name')
            ->orderBy('forum_messages.user_name')
            ->orderBy('forum_messages.user_id')
            ->get();

        $authors = [];
        foreach ($rows as $row) {
            $authors[] = [
                'user_id'   => (int) $row->user_id,
                'user_name' => (string) $row->user_name,
                'count'     => (int) $row->count,
            ];
        }

        return $authors;
    }
}

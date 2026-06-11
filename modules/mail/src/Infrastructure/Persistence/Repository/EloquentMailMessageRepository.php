<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Users\User;

class EloquentMailMessageRepository implements MailMessageRepositoryInterface
{
    public function findById(int $id): ?MailMessage
    {
        return MailMessage::query()->find($id);
    }

    public function getLastMessageBetween(int $authorId, int $recipientId): ?MailMessage
    {
        return MailMessage::query()
            ->where('user_id', $authorId)
            ->where('from_id', $recipientId)
            ->orderByDesc('id')
            ->first();
    }

    public function countConversation(int $userId, int $contactId): int
    {
        return $this->conversationQuery($userId, $contactId)->count();
    }

    /**
     * @return EloquentCollection<int, MailMessage>
     */
    public function getConversation(int $userId, int $contactId, int $limit, int $offset): EloquentCollection
    {
        return $this->conversationQuery($userId, $contactId)
            ->with('recipient')
            ->orderByDesc('time')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<MailMessage>
     */
    private function conversationQuery(int $userId, int $contactId): Builder
    {
        return MailMessage::query()
            ->where(function (Builder $query) use ($userId, $contactId) {
                $query->where(function (Builder $sub) use ($userId, $contactId) {
                    $sub->where('user_id', $userId)
                        ->where('from_id', $contactId);
                })->orWhere(function (Builder $sub) use ($userId, $contactId) {
                    $sub->where('user_id', $contactId)
                        ->where('from_id', $userId);
                });
            })
            ->where('delete', '!=', $userId)
            ->where('sys', '!=', 1)
            ->where('spam', 0);
    }

    /**
     * @return EloquentCollection<int, MailMessage>
     */
    public function getAttachedFiles(int $userId, int $limit, int $offset): EloquentCollection
    {
        return MailMessage::query()
            ->with('recipient')
            ->where(function (Builder $query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('from_id', $userId);
            })
            ->where('delete', 0)
            ->where('file_name', '!=', '')
            ->orderByDesc('time')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function save(MailMessage $message): void
    {
        $message->save();
    }

    public function delete(int $id): void
    {
        MailMessage::query()->where('id', $id)->delete();
    }

    public function markAsRead(int $id): void
    {
        MailMessage::query()->where('id', $id)->update(['read' => true]);
    }

    public function markAsReadByIds(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        MailMessage::query()->whereIn('id', $ids)->update(['read' => true]);
    }

    public function incrementDownloadCount(int $id): void
    {
        MailMessage::query()->where('id', $id)->increment('count');
    }

    public function countMessagesBetween(int $userId, int $contactId): int
    {
        return MailMessage::query()
            ->where(function (Builder $query) use ($userId, $contactId) {
                $query->where(function (Builder $sub) use ($userId, $contactId) {
                    $sub->where('user_id', $userId)
                        ->where('from_id', $contactId);
                })
                    ->orWhere(function (Builder $sub) use ($userId, $contactId) {
                        $sub->where('user_id', $contactId)
                            ->where('from_id', $userId);
                    });
            })
            ->where('sys', '!=', 1)
            ->where('spam', '!=', 1)
            ->where('delete', '!=', $userId)
            ->count();
    }

    public function countNewMessagesFrom(int $userId, int $contactId): int
    {
        return MailMessage::query()
            ->where('user_id', $userId)
            ->where('from_id', $contactId)
            ->where('read', 0)
            ->where('sys', '!=', 1)
            ->where('spam', '!=', 1)
            ->where('delete', '!=', $userId)
            ->count();
    }

    public function getMessagesBetween(int $userId, int $contactId): EloquentCollection
    {
        return MailMessage::query()
            ->where(function (Builder $query) use ($userId, $contactId) {
                $query->where(function (Builder $sub) use ($userId, $contactId) {
                    $sub->where('user_id', $userId)
                        ->where('from_id', $contactId);
                })
                    ->orWhere(function (Builder $sub) use ($userId, $contactId) {
                        $sub->where('user_id', $contactId)
                            ->where('from_id', $userId);
                    });
            })
            ->where('sys', '!=', 1)
            ->where('spam', '!=', 1)
            ->where('delete', '!=', $userId)
            ->get();
    }

    public function countInbox(int $userId): int
    {
        return $this->notBannedContactScope(
            MailMessage::query()->where('cms_mail.from_id', $userId),
            $userId,
            'cms_mail.user_id'
        )
            ->where('cms_mail.delete', '!=', $userId)
            ->where('cms_mail.sys', 0)
            ->where('cms_mail.spam', 0)
            ->count();
    }

    public function countNewInbox(int $userId): int
    {
        return $this->notBannedContactScope(
            MailMessage::query()->where('cms_mail.from_id', $userId),
            $userId,
            'cms_mail.user_id'
        )
            ->where('cms_mail.delete', '!=', $userId)
            ->where('cms_mail.sys', 0)
            ->where('cms_mail.spam', 0)
            ->where('cms_mail.read', 0)
            ->count();
    }

    public function countOutbox(int $userId): int
    {
        return $this->notBannedContactScope(
            MailMessage::query()->where('cms_mail.user_id', $userId),
            $userId,
            'cms_mail.from_id'
        )
            ->where('cms_mail.delete', '!=', $userId)
            ->where('cms_mail.sys', 0)
            ->count();
    }

    public function countNewOutbox(int $userId): int
    {
        return $this->notBannedContactScope(
            MailMessage::query()->where('cms_mail.user_id', $userId),
            $userId,
            'cms_mail.from_id'
        )
            ->where('cms_mail.delete', '!=', $userId)
            ->where('cms_mail.sys', 0)
            ->where('cms_mail.read', 0)
            ->count();
    }

    public function countAttachedFiles(int $userId): int
    {
        return MailMessage::query()
            ->where(function (Builder $query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('from_id', $userId);
            })
            ->where('delete', 0)
            ->where('file_name', '!=', '')
            ->count();
    }

    /**
     * Exclude messages whose counterpart is a banned contact of the current user.
     *
     * @param string $counterpartColumn Qualified column of the other party (sender or recipient).
     */
    private function notBannedContactScope(Builder $query, int $userId, string $counterpartColumn): Builder
    {
        return $query
            ->leftJoin('cms_contact as c', function ($join) use ($userId, $counterpartColumn) {
                $join->on('c.from_id', '=', $counterpartColumn)
                    ->where('c.user_id', '=', $userId);
            })
            ->where(function (Builder $q) {
                $q->where('c.ban', '!=', 1)
                    ->orWhereNull('c.ban');
            });
    }

    public function countIncomingConversations(int $userId): int
    {
        return $this->incomingConversationsQuery($userId)->distinct()->count('m.user_id');
    }

    /**
     * @return Collection<int, User>
     */
    public function getIncomingConversations(int $userId, int $limit, int $offset): Collection
    {
        $rows = $this->incomingConversationsQuery($userId)
            ->select(['m.user_id as id', Capsule::raw('MAX(m.time) as last_time')])
            ->groupBy('m.user_id')
            ->orderByDesc('last_time')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $this->hydrateConversationUsers($rows);
    }

    public function countOutgoingConversations(int $userId): int
    {
        return $this->outgoingConversationsQuery($userId)->distinct()->count('m.from_id');
    }

    /**
     * @return Collection<int, User>
     */
    public function getOutgoingConversations(int $userId, int $limit, int $offset): Collection
    {
        $rows = $this->outgoingConversationsQuery($userId)
            ->select(['m.from_id as id', Capsule::raw('MAX(m.time) as last_time')])
            ->groupBy('m.from_id')
            ->orderByDesc('last_time')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $this->hydrateConversationUsers($rows);
    }

    private function incomingConversationsQuery(int $userId): QueryBuilder
    {
        return Capsule::table('cms_mail as m')
            ->leftJoin('cms_contact as c', function ($join) use ($userId) {
                $join->on('c.from_id', '=', 'm.user_id')
                    ->where('c.user_id', '=', $userId);
            })
            ->where('m.from_id', $userId)
            ->where('m.delete', '!=', $userId)
            ->where('m.sys', 0)
            ->where('m.spam', 0)
            ->where(function ($q) {
                $q->where('c.ban', '!=', 1)
                    ->orWhereNull('c.ban');
            });
    }

    private function outgoingConversationsQuery(int $userId): QueryBuilder
    {
        return Capsule::table('cms_mail as m')
            ->leftJoin('cms_contact as c', function ($join) use ($userId) {
                $join->on('c.from_id', '=', 'm.from_id')
                    ->where('c.user_id', '=', $userId);
            })
            ->where('m.user_id', $userId)
            ->where('m.delete', '!=', $userId)
            ->where('m.sys', 0)
            ->where(function ($q) {
                $q->where('c.ban', '!=', 1)
                    ->orWhereNull('c.ban');
            });
    }

    /**
     * Hydrate grouped conversation rows (id + last_time) into User models, preserving order.
     *
     * @param Collection<int, object> $rows
     * @return Collection<int, User>
     */
    private function hydrateConversationUsers(Collection $rows): Collection
    {
        $userIds = $rows->pluck('id')->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $user = $users[$row->id] ?? null;
            if ($user) {
                $user->last_time = $row->last_time;
                return $user;
            }
            return null;
        })->filter()->values();
    }
}

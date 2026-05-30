<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Infrastructure\Persistence\Repository;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Johncms\Users\User;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;

class EloquentMailMessageRepository implements MailMessageRepositoryInterface
{
    public function findById(int $id): ?MailMessage
    {
        return MailMessage::query()->find($id);
    }

    public function getConversation(int $userId, int $contactId, int $perPage): LengthAwarePaginator
    {
        return MailMessage::query()
            ->with('recipient')
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
            ->where('spam', 0)
            ->orderByDesc('time')
            ->paginate(max(1, $perPage));
    }

    public function getIncomingGrouped(int $userId, int $perPage): array
    {
        // TODO: Implement proper grouping by sender
        $messages = MailMessage::query()
            ->where('user_id', $userId)
            ->where('delete', 0)
            ->orderByDesc('time')
            ->paginate(max(1, $perPage));

        $grouped = [];
        foreach ($messages as $message) {
            $senderId = $message->from_id;
            if (!isset($grouped[$senderId])) {
                $grouped[$senderId] = [
                    'sender' => $message->sender,
                    'last_message' => $message,
                    'unread_count' => 0,
                    'total_count' => 0,
                ];
            }
            $grouped[$senderId]['total_count']++;
            if (!$message->read) {
                $grouped[$senderId]['unread_count']++;
            }
        }

        return [
            'messages' => $messages,
            'grouped' => $grouped,
        ];
    }

    public function getOutgoingGrouped(int $userId, int $perPage): array
    {
        // TODO: Implement proper grouping by recipient
        $messages = MailMessage::query()
            ->where('from_id', $userId)
            ->where('delete', 0)
            ->orderByDesc('time')
            ->paginate(max(1, $perPage));

        $grouped = [];
        foreach ($messages as $message) {
            $recipientId = $message->user_id;
            if (!isset($grouped[$recipientId])) {
                $grouped[$recipientId] = [
                    'recipient' => $message->recipient,
                    'last_message' => $message,
                    'total_count' => 0,
                ];
            }
            $grouped[$recipientId]['total_count']++;
        }

        return [
            'messages' => $messages,
            'grouped' => $grouped,
        ];
    }

    public function getAttachedFiles(int $userId, int $perPage): LengthAwarePaginator
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
            ->paginate(max(1, $perPage));
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

    public function clearConversation(int $userId, int $contactId): void
    {
        // Mark messages as deleted for both users
        MailMessage::query()
            ->where(function (Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $userId)
                    ->where('from_id', $contactId);
            })
            ->orWhere(function (Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $contactId)
                    ->where('from_id', $userId);
            })
            ->update(['delete' => 1]);
    }

    public function countMessagesBetween(int $userId, int $contactId): int
    {
        return MailMessage::query()
            ->where(function (Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $userId)
                    ->where('from_id', $contactId);
            })
            ->orWhere(function (Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $contactId)
                    ->where('from_id', $userId);
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

    public function getMessagesBetween(int $userId, int $contactId): \Illuminate\Database\Eloquent\Collection
    {
        return MailMessage::query()
            ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $userId)
                    ->where('from_id', $contactId);
            })
            ->orWhere(function (\Illuminate\Database\Eloquent\Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $contactId)
                    ->where('from_id', $userId);
            })
            ->where('sys', '!=', 1)
            ->where('spam', '!=', 1)
            ->where('delete', '!=', $userId)
            ->get();
    }

    public function getIncomingConversations(int $userId, int $perPage, int $page): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = DB::table('cms_mail as m')
            ->select(['u.*', DB::raw('MAX(m.time) as last_time')])
            ->join('users as u', 'm.user_id', '=', 'u.id')
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
            })
            ->groupBy('m.user_id', 'u.id')
            ->orderByDesc('last_time');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Convert stdClass items to User models with last_time attribute
        $userIds = collect($paginator->items())->pluck('id')->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $items = collect($paginator->items())->map(function ($row) use ($users) {
            $user = $users[$row->id] ?? null;
            if ($user) {
                $user->last_time = $row->last_time;
                return $user;
            }
            return null;
        })->filter();

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    public function getOutgoingConversations(int $userId, int $perPage, int $page): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = DB::table('cms_mail as m')
            ->select(['u.*', DB::raw('MAX(m.time) as last_time')])
            ->join('users as u', 'm.from_id', '=', 'u.id')
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
            })
            ->groupBy('m.from_id', 'u.id')
            ->orderByDesc('last_time');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Convert stdClass items to User models with last_time attribute
        $userIds = collect($paginator->items())->pluck('id')->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $items = collect($paginator->items())->map(function ($row) use ($users) {
            $user = $users[$row->id] ?? null;
            if ($user) {
                $user->last_time = $row->last_time;
                return $user;
            }
            return null;
        })->filter();

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}

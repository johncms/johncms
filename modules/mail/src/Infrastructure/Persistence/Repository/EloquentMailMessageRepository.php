<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
            ->where(function (Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $userId)
                    ->where('from_id', $contactId);
            })
            ->orWhere(function (Builder $query) use ($userId, $contactId) {
                $query->where('user_id', $contactId)
                    ->where('from_id', $userId);
            })
            ->where('delete', 0)
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
}

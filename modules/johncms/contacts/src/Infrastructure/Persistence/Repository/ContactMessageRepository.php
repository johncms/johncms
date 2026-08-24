<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Infrastructure\Persistence\Repository;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\Modules\Contacts\Domain\Models\ContactMessage;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;

final class ContactMessageRepository implements ContactMessageRepositoryInterface
{
    public function create(array $data): ContactMessage
    {
        return ContactMessage::query()->create($data);
    }

    public function findById(int $id): ?ContactMessage
    {
        return ContactMessage::query()->find($id);
    }

    public function getPage(int $limit, int $offset, ?ContactMessageStatus $status = null): Collection
    {
        return ContactMessage::query()
            ->when($status !== null, static fn($query) => $query->where('status', $status?->value))
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function count(?ContactMessageStatus $status = null): int
    {
        return ContactMessage::query()
            ->when($status !== null, static fn($query) => $query->where('status', $status?->value))
            ->count();
    }

    public function markProcessed(ContactMessage $message): void
    {
        $message->update(
            [
                'status'       => ContactMessageStatus::Processed,
                'processed_at' => Carbon::now(),
            ]
        );
    }

    public function delete(ContactMessage $message): void
    {
        $message->delete();
    }
}

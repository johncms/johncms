<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\UseCases;

use Illuminate\Support\Str;
use Johncms\Modules\Contacts\Application\DTO\ContactMessageListItemDTO;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\Modules\Contacts\Domain\Models\ContactMessage;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;

final readonly class ListContactMessagesUseCase
{
    public function __construct(
        private ContactMessageRepositoryInterface $repository,
    ) {
    }

    public function count(?ContactMessageStatus $status = null): int
    {
        return $this->repository->count($status);
    }

    /**
     * @return list<ContactMessageListItemDTO>
     */
    public function getPage(int $limit, int $offset, ?ContactMessageStatus $status = null): array
    {
        return $this->repository->getPage($limit, $offset, $status)
            ->map(
                static fn(ContactMessage $message): ContactMessageListItemDTO => new ContactMessageListItemDTO(
                    id: $message->id,
                    name: $message->name,
                    email: $message->email,
                    preview: Str::limit(preg_replace('/\s+/u', ' ', $message->message) ?? '', 80),
                    status: $message->status,
                    createdAt: $message->created_at?->format('d.m.Y H:i') ?? '',
                )
            )
            ->all();
    }
}

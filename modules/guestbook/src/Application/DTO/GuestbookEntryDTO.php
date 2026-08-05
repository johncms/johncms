<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

use Twig\Markup;

final readonly class GuestbookEntryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isOnline,
        public string $createdAt,
        public string $updateAt,
        public int $editCount,
        public string $updatedBy,
        public Markup $text,
        public ?Markup $replyText,
        public string $repliedBy,
        public string $repliedAt,
        public int $userId,
        public ?GuestbookEntryUserDTO $user,
        public ?GuestbookEntryMetaDTO $meta
    ) {
    }
}

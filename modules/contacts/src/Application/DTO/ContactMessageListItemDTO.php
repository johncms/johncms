<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\DTO;

use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;

final readonly class ContactMessageListItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $preview,
        public ContactMessageStatus $status,
        public string $createdAt,
    ) {
    }
}

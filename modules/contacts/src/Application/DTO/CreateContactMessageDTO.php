<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\DTO;

final readonly class CreateContactMessageDTO
{
    public function __construct(
        public ?int $userId,
        public string $name,
        public string $email,
        public string $message,
        public string $ip,
        public ?string $userAgent,
    ) {
    }
}

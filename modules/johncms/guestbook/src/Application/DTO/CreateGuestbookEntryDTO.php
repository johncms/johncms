<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

final readonly class CreateGuestbookEntryDTO
{
    public function __construct(
        public bool $adminClub,
        public string $name,
        public string $text,
        public string $ip,
        public string $userAgent,
        /** The entry the text answers; 0 when it answers none. */
        public int $replyTo = 0,
    ) {
    }
}

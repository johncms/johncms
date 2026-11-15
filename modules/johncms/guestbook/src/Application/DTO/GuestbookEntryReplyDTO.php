<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

/** The entry an entry answers, as the answer recalls it: who wrote it, how it starts, where it is. */
final readonly class GuestbookEntryReplyDTO
{
    public function __construct(
        public int $id,
        public string $authorName,
        public string $excerpt,
        public string $url,
    ) {
    }
}

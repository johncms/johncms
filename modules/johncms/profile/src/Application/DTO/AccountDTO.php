<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class AccountDTO
{
    public function __construct(
        public int $userId,
        public int $totalPhoto,
        public int $guestbookCount,
        public int $inbox,
        public int $newMessages,
        public int $outbox,
        public int $unreadSent,
        public int $files,
        public int $contacts,
        public int $blockedContacts,
    ) {
    }
}

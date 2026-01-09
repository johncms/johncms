<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

final readonly class GuestbookEntryUserDTO
{
    public function __construct(
        public int $id,
        public string $profileUrl,
        public string $rightsName,
        public int $rights,
        public string $status,
    ) {
    }
}

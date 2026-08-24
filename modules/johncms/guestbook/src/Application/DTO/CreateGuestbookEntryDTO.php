<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

final readonly class CreateGuestbookEntryDTO
{
    /**
     * @param int[] $attachedFiles
     */
    public function __construct(
        public bool $adminClub,
        public string $name,
        public string $text,
        public string $ip,
        public string $userAgent,
        public array $attachedFiles,
    ) {
    }
}

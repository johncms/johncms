<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\DTO;

/** Where an entry is shown now: in which of the two guestbooks and on which page of it. */
final readonly class GuestbookEntryPlaceDTO
{
    public function __construct(
        public int $id,
        public bool $adminClub,
        public int $page,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Users\User;

final readonly class EnsureGuestbookEntryManageAccessUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    public function execute(GuestbookEntry $entry): void
    {
        if ($this->currentUser->rights < 1) {
            throw new GuestbookAccessDeniedException();
        }

        if ($entry->user !== null && $entry->user->rights > $this->currentUser->rights) {
            throw new GuestbookAccessDeniedException();
        }
    }
}

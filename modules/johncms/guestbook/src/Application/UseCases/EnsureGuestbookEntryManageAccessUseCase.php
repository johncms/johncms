<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;

final readonly class EnsureGuestbookEntryManageAccessUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private RoleLevels $roleLevels,
    ) {
    }

    public function execute(GuestbookEntry $entry): void
    {
        if (! $this->accessChecker->allows(GuestbookPermissions::ENTRY_MANAGE)) {
            throw new GuestbookAccessDeniedException();
        }

        // Nobody touches the entry of somebody standing above them
        if (
            $entry->user !== null
            && $this->roleLevels->highestGrantedTo($entry->user->id) > $this->roleLevels->highest($this->currentUser->identity())
        ) {
            throw new GuestbookAccessDeniedException();
        }
    }
}

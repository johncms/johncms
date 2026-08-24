<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Access;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;

final readonly class GuestbookMode
{
    private const SESSION_KEY = 'ga';

    public function __construct(
        private CurrentUser $currentUser,
        private Session $session,
        private AccessCheckerInterface $accessChecker,
        private array $guestAccess = [],
    ) {
    }

    public function isAdminClub(): bool
    {
        return $this->session->has(self::SESSION_KEY) && $this->hasAdminClubAccess();
    }

    public function isGuestbook(): bool
    {
        return ! $this->isAdminClub();
    }

    public function switch(Request $request): void
    {
        if (! $this->hasAdminClubAccess()) {
            return;
        }

        if ($request->queryParam('do') === 'set') {
            $this->session->set(self::SESSION_KEY, 1);
        } else {
            $this->session->remove(self::SESSION_KEY);
        }
    }

    private function hasAdminClubAccess(): bool
    {
        return $this->accessChecker->allows(GuestbookPermissions::ADMIN_CLUB_VIEW)
            || in_array($this->currentUser->id(), $this->guestAccess, true);
    }
}

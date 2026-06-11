<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Access;

use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\Users\User;

final readonly class GuestbookMode
{
    private const SESSION_KEY = 'ga';

    public function __construct(
        private User $user,
        private Session $session,
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

        if ($request->getQuery('do') === 'set') {
            $this->session->set(self::SESSION_KEY, 1);
        } else {
            $this->session->remove(self::SESSION_KEY);
        }
    }

    private function hasAdminClubAccess(): bool
    {
        return $this->user->rights >= 1 || in_array($this->user->id, $this->guestAccess, true);
    }
}

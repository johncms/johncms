<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Access;

use Johncms\System\Http\Request;
use Johncms\Users\User;

final readonly class GuestbookMode
{
    public function __construct(
        private User $user,
        private array $guestAccess = [],
    ) {
    }

    public function isAdminClub(): bool
    {
        return isset($_SESSION['ga'])
            && ($this->user->rights >= 1 || in_array($this->user->id, $this->guestAccess, true));
    }

    public function isGuestbook(): bool
    {
        return ! $this->isAdminClub();
    }

    public function switch(Request $request): void
    {
        if ($this->user->rights < 1 && ! in_array($this->user->id, $this->guestAccess, true)) {
            return;
        }

        if ($request->getQuery('do') === 'set') {
            $_SESSION['ga'] = 1;
        } else {
            unset($_SESSION['ga']);
        }
    }
}

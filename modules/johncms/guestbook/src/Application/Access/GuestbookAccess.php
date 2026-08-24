<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Access;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;

final readonly class GuestbookAccess
{
    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function canRead(): bool
    {
        return $this->accessChecker->allows(GuestbookPermissions::VIEW);
    }

    public function canWrite(): bool
    {
        return $this->accessChecker->allows(GuestbookPermissions::POST)
            && ! isset($this->currentUser->user()->ban['1'])
            && ! isset($this->currentUser->user()->ban['13']);
    }

    public function canClear(): bool
    {
        return $this->accessChecker->allows(GuestbookPermissions::CLEAR);
    }
}

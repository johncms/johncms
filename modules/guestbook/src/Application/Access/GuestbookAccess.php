<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Access;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Users\User;

final readonly class GuestbookAccess
{
    public function __construct(
        private User $user,
        private AccessCheckerInterface $accessChecker,
        private array $config,
    ) {
    }

    public function canWrite(): bool
    {
        return ($this->user->isValid() || $this->config['mod_guest'] === 2)
            && ! isset($this->user->ban['1'])
            && ! isset($this->user->ban['13']);
    }

    public function canClear(): bool
    {
        return $this->accessChecker->allows(GuestbookPermissions::CLEAR);
    }

    public function isClosed(): bool
    {
        return ! $this->config['mod_guest'];
    }
}

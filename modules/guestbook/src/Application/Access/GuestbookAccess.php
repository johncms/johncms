<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Access;

use Johncms\Users\User;

final readonly class GuestbookAccess
{
    public function __construct(
        private User $user,
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
        return $this->user->rights >= 7;
    }

    public function isClosed(): bool
    {
        return ! $this->config['mod_guest'];
    }
}

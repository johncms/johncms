<?php

declare(strict_types=1);

namespace Johncms\Online;

use Johncms\Online\Models\GuestSession;
use Johncms\Users\User;

class OnlineCounter
{
    public function getUsers(): int
    {
        return User::query()->online()->count();
    }

    public function getGuests(): int
    {
        return GuestSession::query()->online()->count();
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Online;

use Carbon\Carbon;
use Johncms\Users\User;

class Online
{
    public function updateOnline()
    {
        $user = di(User::class);
        $user?->updateActivity(
            [
                'last_visit' => Carbon::now(),
            ]
        );
    }
}

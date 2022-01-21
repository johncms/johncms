<?php

declare(strict_types=1);

namespace Johncms\Online;

use Carbon\Carbon;
use Johncms\Users\User;
use Throwable;

class Online
{
    public function updateOnline()
    {
        $user = di(User::class);

        $fields = [
            'last_visit' => Carbon::now(),
        ];

        try {
            $route = di('route');
            $fields['route'] = $route?->getName();
            $fields['route_params'] = $route?->getVars();
        } catch (Throwable) {
        }

        $user?->updateActivity($fields);
    }
}

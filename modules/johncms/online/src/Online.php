<?php

declare(strict_types=1);

namespace Johncms\Online;

use Carbon\Carbon;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Throwable;

class Online
{
    public function updateOnline()
    {
        $user = di(User::class);
        $request = di(Request::class);
        $session = di(Session::class);

        $fields = [
            'last_visit'   => Carbon::now(),
            'ip'           => $request->getIp(),
            'ip_via_proxy' => null,
            'user_agent'   => $request->getUserAgent(),
        ];

        $ipViaProxy = $request->getIpViaProxy();
        if ($ipViaProxy !== '127.0.0.1') {
            $fields['ip_via_proxy'] = $ipViaProxy;
        }

        if (! $session->has('session_started')) {
            $fields['session_started'] = Carbon::now();
            $session->set('session_started', true);
        }

        try {
            $route = di('route');
            $fields['route'] = $route?->getName();
            $fields['route_params'] = $route?->getVars();
        } catch (Throwable) {
        }

        $user?->updateActivity($fields);
    }
}

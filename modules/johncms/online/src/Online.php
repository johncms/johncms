<?php

declare(strict_types=1);

namespace Johncms\Online;

use Carbon\Carbon;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Online\Models\GuestSession;
use Johncms\Users\User;
use Throwable;

class Online
{
    private ?User $user;
    private Request $request;
    private Session $session;

    public function __construct(Request $request, Session $session, ?User $user)
    {
        $this->request = $request;
        $this->session = $session;
        $this->user = $user;
    }

    public function updateOnline()
    {
        if ($this->user) {
            $this->updateUserOnline();
        } else {
            $this->updateGuestOnline();
        }
    }

    public function updateUserOnline(): void
    {
        $fields = [
            'last_visit'   => Carbon::now(),
            'ip'           => $this->request->getIp(),
            'ip_via_proxy' => null,
            'user_agent'   => $this->request->getUserAgent(),
        ];

        $ipViaProxy = $this->request->getIpViaProxy();
        if ($ipViaProxy !== '127.0.0.1') {
            $fields['ip_via_proxy'] = $ipViaProxy;
        }

        if (! $this->session->has('session_started')) {
            $fields['session_started'] = Carbon::now();
            $this->session->set('session_started', true);
        }

        try {
            $route = di('route');
            $fields['route'] = $route?->getName();
            $fields['route_params'] = $route?->getVars();
        } catch (Throwable) {
        }

        $this->user?->updateActivity($fields);
    }

    public function updateGuestOnline(): void
    {
        $sessionId = md5($this->request->getIp() . $this->request->getIpViaProxy() . $this->request->getUserAgent());

        $fields = [
            'updated_at'   => Carbon::now(),
            'ip'           => $this->request->getIp(),
            'ip_via_proxy' => null,
            'user_agent'   => $this->request->getUserAgent(),
        ];

        $ipViaProxy = $this->request->getIpViaProxy();
        if ($ipViaProxy !== '127.0.0.1') {
            $fields['ip_via_proxy'] = $ipViaProxy;
        }

        try {
            $route = di('route');
            $fields['route'] = $route?->getName();
            $fields['route_params'] = $route?->getVars();
        } catch (Throwable) {
        }

        $session = GuestSession::query()->where('id', $sessionId)->first();
        if ($session) {
            // Update the session in exists
            $fields['movements'] = $session->movements + 1;
            $session->update($fields);
        } else {
            // Create the session in not exists
            $fields['id'] = $sessionId;
            $fields['created_at'] = Carbon::now();
            $fields['route_params'] = json_encode((array) ($fields['route_params'] ?? []));
            GuestSession::query()->insertOrIgnore($fields);
        }
    }
}

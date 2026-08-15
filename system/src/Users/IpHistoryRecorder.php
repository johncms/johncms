<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Environment;

/**
 * Keeps the trail of addresses a signed-in visitor came from.
 *
 * A write, so it belongs to the request cycle and is called by the kernel: resolving a service
 * must not be what records a visit. The previous address is what goes into the history — the
 * current one lives in the users table and is moved down here once it changes.
 */
final readonly class IpHistoryRecorder
{
    public function __construct(
        private CurrentUser $currentUser,
        private Environment $env,
    ) {
    }

    public function record(): void
    {
        if ($this->currentUser->isGuest()) {
            return;
        }

        $user = $this->currentUser->user();
        $ipViaProxy = $this->env->getIpViaProxy(false);
        $ipViaProxy = empty($ipViaProxy) ? '' : $ipViaProxy;

        if ($user->ip_via_proxy === $ipViaProxy && $user->ip === $this->env->getIp(false)) {
            return;
        }

        $history = $user->ipHistory();
        // The address about to be recorded may already be in the history from an earlier visit,
        // and the row it gets is the recent one.
        $history->where('ip', '=', $this->env->getIp())
            ->where('ip_via_proxy', '=', $this->env->getIpViaProxy())
            ->delete();

        $history->create(
            [
                'user_id'      => $user->id,
                'ip'           => ip2long($user->ip),
                'ip_via_proxy' => ip2long($user->ip_via_proxy),
                'time'         => $user->lastdate,
            ]
        );

        $user->ip = $this->env->getIp(false);
        $user->ip_via_proxy = empty($ipViaProxy) ? 0 : $ipViaProxy;
        $user->save();
    }
}

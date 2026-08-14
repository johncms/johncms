<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Session;

/**
 * Reads the session settings out of config/autoload/auth.*.php.
 *
 * A factory rather than constructor arguments wired in services.php: the container is compiled
 * and cached, so anything resolved while building it would be frozen into the cache and a
 * changed configuration would keep being ignored until the cache is cleared.
 */
final class SessionSettingsFactory
{
    public function __invoke(): SessionSettings
    {
        $defaults = new SessionSettings();

        $absoluteLifetime = config('auth.session.absolute_lifetime');

        return new SessionSettings(
            cookieName: (string) config('auth.session.cookie_name', $defaults->cookieName),
            tokenBytes: (int) config('auth.session.token_bytes', $defaults->tokenBytes),
            lifetime: (int) config('auth.session.lifetime', $defaults->lifetime),
            idleLifetime: (int) config('auth.session.idle_lifetime', $defaults->idleLifetime),
            renewInterval: (int) config('auth.session.renew_interval', $defaults->renewInterval),
            absoluteLifetime: $absoluteLifetime === null ? null : (int) $absoluteLifetime,
            rememberByDefault: (bool) config('auth.session.remember_by_default', $defaults->rememberByDefault),
        );
    }
}

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
 * The lifetimes and the cookie name the session layer works by, resolved from config once per
 * process by SessionSettingsFactory.
 *
 * A value object rather than config() calls scattered through the manager: a test wanting a
 * five-second lifetime builds one instead of rewriting the configuration.
 */
final readonly class SessionSettings
{
    /**
     * @param string   $cookieName        Name of the cookie carrying the secret.
     * @param int      $tokenBytes        Entropy of that secret before encoding.
     * @param int      $lifetime          Sliding lifetime of a remembered session, in seconds.
     * @param int      $idleLifetime      Lifetime without "remember me", in seconds.
     * @param int      $renewInterval     How stale the sliding extension may get before it is
     *                                    written again — extending on every request would mean
     *                                    a write per request.
     * @param int|null $absoluteLifetime  Cap that is never extended; null disables it.
     * @param bool     $rememberByDefault Whether the sign-in forms pre-check "remember me".
     */
    public function __construct(
        public string $cookieName = 'jc_auth',
        public int $tokenBytes = 32,
        public int $lifetime = 2592000,
        public int $idleLifetime = 43200,
        public int $renewInterval = 300,
        public ?int $absoluteLifetime = null,
        public bool $rememberByDefault = true,
    ) {
    }

    /**
     * How long a session of this kind lives from the moment it is used.
     */
    public function lifetimeFor(bool $remember): int
    {
        return $remember ? $this->lifetime : $this->idleLifetime;
    }
}

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

use Johncms\Auth\Impersonation\ImpersonationSettings;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Builds the sign-in cookie. The single place that does, so its flags cannot be forgotten in
 * one of the several screens that sign a visitor in — which is how the previous cookies ended
 * up readable by any script and sent over plain HTTP.
 *
 * Always HttpOnly: a cookie script can read is a cookie an XSS steals. Always SameSite=Lax,
 * which keeps it off cross-site requests while surviving ordinary navigation to the site.
 * Secure follows the scheme of the request being answered — forcing it on a site served over
 * plain HTTP would make signing in silently impossible.
 */
final readonly class AuthCookieFactory
{
    public function __construct(
        private SessionSettings $settings,
        private ImpersonationSettings $impersonation,
    ) {
    }

    /**
     * @param bool     $remember  A remembered session gets a persistent cookie; without it the
     *                            cookie is a session one and the browser drops it on its own.
     *                            The row expires regardless, which is what actually limits the
     *                            visit: browsers restore session cookies when asked to reopen
     *                            the previous tabs.
     * @param int|null $expiresAt When the session row expires; ignored for a session cookie.
     */
    public function create(string $token, bool $remember, ?int $expiresAt, bool $secure): Cookie
    {
        return $this->build($this->settings->cookieName, $token, $remember ? ($expiresAt ?? 0) : 0, $secure);
    }

    /**
     * Tells the browser to drop the cookie: same name and attributes, already expired.
     */
    public function forget(bool $secure): Cookie
    {
        return $this->build($this->settings->cookieName, '', 1, $secure);
    }

    /**
     * The cookie the administrator's own session waits in while they browse as somebody else.
     *
     * Built here rather than in the impersonation code so it carries exactly the flags of the
     * sign-in cookie — it holds a secret of the same value, and a second place building cookies
     * is how one of them ends up without HttpOnly.
     */
    public function createParent(string $token, int $expiresAt, bool $secure): Cookie
    {
        return $this->build($this->impersonation->parentCookieName, $token, $expiresAt, $secure);
    }

    public function forgetParent(bool $secure): Cookie
    {
        return $this->build($this->impersonation->parentCookieName, '', 1, $secure);
    }

    private function build(string $name, string $value, int $expire, bool $secure): Cookie
    {
        return Cookie::create(
            name: $name,
            value: $value,
            expire: $expire,
            path: '/',
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }
}

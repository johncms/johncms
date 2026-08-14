<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authentication;

use Johncms\Auth\AuthMethod;
use Johncms\Auth\Identity;
use Johncms\Auth\Session\AuthCookieFactory;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Http\CookieQueue;
use Johncms\Http\Request;

/**
 * Identifies the visitor by the sign-in cookie.
 *
 * Also where the sliding lifetime is applied: a visit moves the window forward, and whenever the
 * row is extended the cookie is reissued with it. Those two must happen together — a row that
 * outlives its cookie signs the visitor out anyway, which is the classic way a "sliding" session
 * turns out to expire exactly one lifetime after the sign-in.
 *
 * A cookie that matches nothing is not an error: it belongs to a session that was signed out,
 * expired or never existed. It is dropped from the browser and the visitor continues as a guest.
 */
final readonly class SessionCookieAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private AuthSessionManager $sessions,
        private AuthCookieFactory $cookies,
        private CookieQueue $cookieQueue,
    ) {
    }

    public function authenticate(Request $request): ?Identity
    {
        $cookieName = $this->sessions->settings()->cookieName;
        $token = $request->cookies->getString($cookieName, '');

        if ($token === '') {
            return null;
        }

        $session = $this->sessions->find($token);

        if ($session === null) {
            $this->cookieQueue->add($this->cookies->forget($request->isSecure()));

            return null;
        }

        $expiresAt = $this->sessions->touch($session);

        if ($expiresAt !== null) {
            $this->cookieQueue->add(
                $this->cookies->create($token, $session->remember, $expiresAt, $request->isSecure())
            );
        }

        return new Identity(
            userId: $session->user_id,
            method: AuthMethod::Session,
            impersonatorId: $session->impersonator_id,
        );
    }
}

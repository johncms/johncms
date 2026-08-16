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
use Johncms\Auth\Impersonation\ImpersonationSettings;
use Johncms\Auth\Session\AuthCookieFactory;
use Johncms\Auth\Session\AuthSession;
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
 * expired or never existed. It is dropped from the browser and the visitor continues as a guest —
 * unless an administrator's own session is waiting in the parent cookie, in which case they
 * simply become themselves again.
 */
final readonly class SessionCookieAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private AuthSessionManager $sessions,
        private AuthCookieFactory $cookies,
        private CookieQueue $cookieQueue,
        private ImpersonationSettings $impersonation,
    ) {
    }

    public function authenticate(Request $request): ?Identity
    {
        $token = $request->cookies->getString($this->sessions->settings()->cookieName, '');
        $session = $token === '' ? null : $this->sessions->find($token);

        if ($session === null) {
            return $this->fallBackToParent($request, dropCookie: $token !== '');
        }

        if (! $session->is_impersonation) {
            $expiresAt = $this->sessions->touch($session);

            if ($expiresAt !== null) {
                $this->cookieQueue->add(
                    $this->cookies->create($token, $session->remember, $expiresAt, $request->isSecure())
                );
            }
        }
        // An impersonated session is deliberately not extended: it lasts the hour it was given,
        // whether or not the administrator keeps clicking. Starting again is one click, and it
        // gives the audit trail clean boundaries instead of one session lasting all day.

        return $this->identityOf($session);
    }

    /**
     * The administrator returns to themselves when the impersonated session has run out, without
     * having to do anything about it: the session they came from is waiting in the parent cookie.
     */
    private function fallBackToParent(Request $request, bool $dropCookie): ?Identity
    {
        $parentToken = $request->cookies->getString($this->impersonation->parentCookieName, '');
        $parent = $parentToken === '' ? null : $this->sessions->find($parentToken);

        if ($parent === null) {
            // Only what the browser actually holds is cleared: a visitor who arrived with no
            // cookies at all must not be answered with headers dropping them.
            if ($dropCookie) {
                $this->cookieQueue->add($this->cookies->forget($request->isSecure()));
            }

            if ($parentToken !== '') {
                $this->cookieQueue->add($this->cookies->forgetParent($request->isSecure()));
            }

            return null;
        }

        $this->cookieQueue->add(
            $this->cookies->create($parentToken, $parent->remember, $parent->expires_at, $request->isSecure())
        );
        $this->cookieQueue->add($this->cookies->forgetParent($request->isSecure()));

        return $this->identityOf($parent);
    }

    private function identityOf(AuthSession $session): Identity
    {
        return new Identity(
            userId: $session->user_id,
            method: AuthMethod::Session,
            impersonatorId: $session->impersonator_id,
            sessionId: $session->id,
        );
    }
}

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

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Http\CookieQueue;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\Session as PhpSession;

/**
 * What signing in and out actually consist of, in one place.
 *
 * There is more than one screen that signs a visitor in — the public form, the admin panel,
 * the end of registration, later the external providers — and each of them used to do the whole
 * thing itself: set two cookies, with its own flags, in its own order. Collecting it here is
 * what makes the flags, the PHP session handling and the audit trail identical everywhere.
 *
 * Takes a Request for the same reason the authenticators do: it is the layer that adapts HTTP,
 * and it needs the scheme of the request to decide whether the cookie may be marked Secure.
 */
final readonly class SignInManager
{
    public function __construct(
        private AuthSessionManager $sessions,
        private AuthSessionRepositoryInterface $repository,
        private AuthCookieFactory $cookies,
        private CookieQueue $cookieQueue,
        private Environment $environment,
        private PhpSession $phpSession,
        private CurrentUser $currentUser,
        private AuthEventLoggerInterface $eventLogger,
    ) {
    }

    /**
     * Opens a session for the user and hands the browser the cookie for it.
     */
    public function signIn(int $userId, bool $remember, Request $request): AuthSession
    {
        // A new identifier for the PHP session as well: whatever value somebody may have planted
        // in the visitor's browser before they signed in must not survive the sign-in.
        $this->phpSession->migrate();

        $issued = $this->sessions->start($userId, $remember, $this->environment->getClientInfo());

        $this->cookieQueue->add(
            $this->cookies->create(
                $issued->token,
                $remember,
                $issued->session->expires_at,
                $request->isSecure()
            )
        );

        return $issued->session;
    }

    /**
     * Closes the session this request arrived on and clears the browser.
     */
    public function signOut(Request $request): void
    {
        $sessionId = $this->currentUser->identity()->sessionId;

        if ($sessionId !== null) {
            $session = $this->repository->findById($sessionId);

            if ($session !== null) {
                $this->sessions->revoke($session, SessionRevocationReason::Logout);
                $this->eventLogger->log(AuthEventType::Logout, $session->user_id);
            }
        }

        $this->cookieQueue->add($this->cookies->forget($request->isSecure()));
        $this->phpSession->invalidate();
    }

    /**
     * Closes every other session of the user — the "sign out on all my other devices" of the
     * profile, and what a password change does on its own.
     *
     * @return int Number of sessions closed.
     */
    public function signOutEverywhereElse(int $userId, SessionRevocationReason $reason): int
    {
        $closed = $this->sessions->revokeAllFor($userId, $reason, $this->currentUser->identity()->sessionId);

        if ($closed > 0) {
            $this->eventLogger->log(
                AuthEventType::SessionRevoked,
                $userId,
                [
                    'reason' => $reason->value,
                    'count'  => $closed,
                ]
            );
        }

        return $closed;
    }
}

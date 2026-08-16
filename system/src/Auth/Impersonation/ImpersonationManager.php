<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Impersonation;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Identity;
use Johncms\Auth\Session\AuthCookieFactory;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\AuthSessionRepositoryInterface;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Http\CookieQueue;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Users\Repository\UserRepositoryInterface;

/**
 * Browsing the site as another account, and coming back out of it.
 *
 * Not a swapped cookie: impersonation is a session row of its own, carrying who opened it and
 * which session to return to. That is what makes it visible in the audit trail and revocable like
 * any other session — a swapped cookie would be indistinguishable from the user signing in
 * themselves, which is precisely what must not happen.
 *
 * The administrator's own session is neither closed nor overwritten: it waits in a second cookie
 * and is put back when they return. So closing the browser mid-impersonation is harmless — the
 * impersonated session dies on its own within the hour and the next request falls back to the
 * session that was waiting.
 */
final readonly class ImpersonationManager
{
    public function __construct(
        private AuthSessionManager $sessions,
        private AuthSessionRepositoryInterface $repository,
        private AuthCookieFactory $cookies,
        private CookieQueue $cookieQueue,
        private Environment $environment,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private RoleLevels $roleLevels,
        private UserRepositoryInterface $users,
        private AuthEventLoggerInterface $eventLogger,
        private ImpersonationSettings $settings,
    ) {
    }

    /**
     * Opens a session as the given account and hands the browser its cookie, keeping the
     * administrator's own session in the parent cookie.
     *
     * @throws ImpersonationNotAllowedException
     */
    public function start(int $targetUserId, Request $request): void
    {
        $identity = $this->currentUser->identity();

        $this->ensureAllowed($targetUserId, $identity);

        $issued = $this->sessions->start(
            $targetUserId,
            // Never remembered: the session is meant to last an hour and to leave no persistent
            // cookie behind on whatever machine the administrator used.
            remember: false,
            client: $this->environment->getClientInfo(),
            impersonatorId: $identity->userId,
            parentSessionId: $identity->sessionId,
            lifetimeOverride: $this->settings->lifetime,
        );

        $parentToken = $request->cookies->getString($this->sessions->settings()->cookieName, '');

        if ($parentToken !== '') {
            $this->cookieQueue->add(
                $this->cookies->createParent(
                    $parentToken,
                    time() + $this->settings->lifetime,
                    $request->isSecure()
                )
            );
        }

        $this->cookieQueue->add(
            $this->cookies->create($issued->token, false, $issued->session->expires_at, $request->isSecure())
        );

        $this->eventLogger->log(
            AuthEventType::ImpersonationStart,
            $targetUserId,
            ['session_id' => $issued->session->id],
            actorId: $identity->userId
        );
    }

    /**
     * Ends the impersonation of the current request and puts the administrator back.
     *
     * @return bool Whether there was an impersonation to end.
     */
    public function stop(Request $request): bool
    {
        $identity = $this->currentUser->identity();

        if (! $identity->isImpersonating()) {
            return false;
        }

        if ($identity->sessionId !== null) {
            $session = $this->repository->findById($identity->sessionId);

            if ($session !== null) {
                $this->sessions->revoke($session, SessionRevocationReason::ImpersonationStop);
            }
        }

        $parentToken = $request->cookies->getString($this->settings->parentCookieName, '');
        $parent = $parentToken === '' ? null : $this->sessions->find($parentToken);

        if ($parent !== null) {
            $this->cookieQueue->add(
                $this->cookies->create($parentToken, $parent->remember, $parent->expires_at, $request->isSecure())
            );
        } else {
            // The administrator's own session expired while they were somebody else. Nothing to
            // come back to, so the browser is cleared rather than left holding a dead secret.
            $this->cookieQueue->add($this->cookies->forget($request->isSecure()));
        }

        $this->cookieQueue->add($this->cookies->forgetParent($request->isSecure()));

        $this->eventLogger->log(
            AuthEventType::ImpersonationStop,
            $identity->userId,
            ['session_id' => $identity->sessionId],
            actorId: $identity->impersonatorId
        );

        return true;
    }

    /**
     * @throws ImpersonationNotAllowedException
     */
    private function ensureAllowed(int $targetUserId, Identity $identity): void
    {
        if (! $this->accessChecker->allows(CorePermissions::USERS_IMPERSONATE)) {
            throw new ImpersonationNotAllowedException(__('Access denied'));
        }

        if ($targetUserId === $identity->userId) {
            throw new ImpersonationNotAllowedException(__('You are already signed in as yourself'));
        }

        if ($this->users->find($targetUserId) === null) {
            throw new ImpersonationNotAllowedException(__('User does not exists'));
        }

        // The same hierarchy the role editor obeys: without it, browsing as somebody would be the
        // way around every "you cannot act on somebody who outranks you" check there is.
        $ownLevel = $this->roleLevels->highest($identity);

        if ($this->roleLevels->highestGrantedTo($targetUserId) >= $ownLevel) {
            throw new ImpersonationNotAllowedException(__('You cannot browse as somebody who outranks you'));
        }
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Auth\Session\SignInManager;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\UseCases\GetUserSessionsUseCase;
use Johncms\Modules\Profile\Application\UseCases\RevokeUserSessionUseCase;
use Johncms\NavChain;

/**
 * "My devices": where the account is signed in and how to close any of it.
 *
 * The screen only exists because sessions live on the server now. With the previous cookie
 * there was nothing to list and nothing to close.
 */
final readonly class SessionsController
{
    private const URL = '/profile/sessions';

    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private CurrentUser $currentUser,
        private GetUserSessionsUseCase $getUserSessions,
        private RevokeUserSessionUseCase $revokeUserSession,
        private SignInManager $signInManager,
    ) {
    }

    public function index(): ViewResponse
    {
        $title = __('My Devices');
        $this->navChain->add(__('Personal'), '/profile/account');
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/sessions.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'sessions'        => $this->getUserSessions->execute(),
                'form_action'     => self::URL,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    public function revoke(Request $request): void
    {
        $sessionId = $request->bodyInt('session_id');

        // Closing the session this page is open in is signing out, and has to clear the cookie
        // as well — otherwise the browser keeps sending a token that no longer works.
        if ($sessionId === $this->currentUser->identity()->sessionId) {
            $this->signInManager->signOut($request);
            redirect('/');
        }

        if ($this->revokeUserSession->execute($sessionId)) {
            $this->session->flash('success_message', __('The device has been signed out'));
        }

        redirect(self::URL);
    }

    public function revokeOthers(): void
    {
        $closed = $this->signInManager->signOutEverywhereElse(
            $this->currentUser->identity()->userId,
            SessionRevocationReason::Logout
        );

        $this->session->flash(
            'success_message',
            sprintf(n__('%d device has been signed out', '%d devices have been signed out', $closed), $closed)
        );

        redirect(self::URL);
    }
}

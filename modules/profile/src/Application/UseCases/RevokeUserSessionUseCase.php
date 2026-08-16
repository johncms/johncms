<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\AuthSessionRepositoryInterface;
use Johncms\Auth\Session\SessionRevocationReason;

/**
 * Signs one of the visitor's own devices out.
 *
 * The ownership check is the whole point: the identifier comes from a form, so without it
 * anybody could close anybody else's session by guessing a number.
 */
final readonly class RevokeUserSessionUseCase
{
    public function __construct(
        private AuthSessionManager $sessions,
        private AuthSessionRepositoryInterface $repository,
        private CurrentUser $currentUser,
        private AuthEventLoggerInterface $eventLogger,
    ) {
    }

    /**
     * @return bool Whether there was such a session of this user to close.
     */
    public function execute(int $sessionId): bool
    {
        $identity = $this->currentUser->identity();
        $session = $this->repository->findById($sessionId);

        if ($session === null || $session->user_id !== $identity->userId || $session->is_impersonation) {
            return false;
        }

        $this->sessions->revoke($session, SessionRevocationReason::Logout);
        $this->eventLogger->log(
            AuthEventType::SessionRevoked,
            $session->user_id,
            [
                'session_id' => $session->id,
                'reason'     => SessionRevocationReason::Logout->value,
                'count'      => 1,
            ]
        );

        return true;
    }
}

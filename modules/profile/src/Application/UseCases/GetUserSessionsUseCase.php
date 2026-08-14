<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Session\AuthSession;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Modules\Profile\Application\DTO\UserSessionDTO;

/**
 * The devices the visitor is signed in on.
 *
 * Only their own: nobody is shown somebody else's sessions, and an administrator browsing as
 * this user is not shown at all — AuthSessionManager leaves impersonation sessions out of the
 * list, so the visit stays invisible to the account it was made under.
 */
final readonly class GetUserSessionsUseCase
{
    public function __construct(
        private AuthSessionManager $sessions,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @return list<UserSessionDTO>
     */
    public function execute(): array
    {
        $identity = $this->currentUser->identity();

        return $this->sessions->activeFor($identity->userId)
            ->map(
                static fn (AuthSession $session): UserSessionDTO => new UserSessionDTO(
                    id: $session->id,
                    ip: $session->ip,
                    userAgent: $session->user_agent,
                    lastUsedAt: $session->last_used_at,
                    createdAt: $session->created_at,
                    expiresAt: $session->expires_at,
                    remember: $session->remember,
                    isCurrent: $session->id === $identity->sessionId,
                )
            )
            ->values()
            ->all();
    }
}

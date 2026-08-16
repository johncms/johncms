<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Events;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Environment;

/**
 * Writes the audit trail.
 *
 * Everything that changes who may do what — signing in, changing a password, closing a device,
 * granting a role — leaves an entry here. The point is answering "what happened to this account
 * and who did it" afterwards, which is the one question the previous scheme could never answer:
 * it kept no record at all.
 *
 * The address and the browser are taken from the request rather than passed in, so no caller can
 * forget them; the actor is taken from the current identity for the same reason.
 */
final readonly class AuthEventLogger implements AuthEventLoggerInterface
{
    public function __construct(
        private AuthEventRepositoryInterface $repository,
        private Environment $environment,
        private CurrentUser $currentUser,
    ) {
    }

    public function log(
        AuthEventType|string $event,
        ?int $userId = null,
        array $context = [],
        ?int $actorId = null,
        ?int $now = null,
    ): void {
        $client = $this->environment->getClientInfo();

        $this->repository->store(
            [
                'user_id'    => $userId,
                'actor_id'   => $actorId ?? $this->actor($userId),
                'event'      => $event instanceof AuthEventType ? $event->value : $event,
                'ip'         => $client->ip,
                'user_agent' => mb_substr($client->userAgent, 0, 255),
                'context'    => $context === [] ? null : $context,
                'created_at' => $now ?? time(),
            ]
        );
    }

    /**
     * Who is doing this, when it is somebody other than the account the event is about: an
     * administrator acting on a user, or the administrator behind an impersonated session.
     *
     * Left empty when the visitor is acting on their own account — repeating the same id in both
     * columns would only make the log harder to read.
     */
    private function actor(?int $userId): ?int
    {
        $identity = $this->currentUser->identity();
        $actorId = $identity->impersonatorId ?? $identity->userId;

        if ($actorId === 0 || $actorId === $userId) {
            return null;
        }

        return $actorId;
    }
}

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

use Illuminate\Support\Collection;
use Johncms\Auth\SecureToken;
use Johncms\Security\ClientInfoDTO;

/**
 * Opens, extends, rotates and closes the sessions behind the sign-in cookie.
 *
 * Free of HTTP on purpose: it deals in tokens and rows, and turning a token into a cookie is
 * AuthCookieFactory's job. That keeps the lifetime arithmetic testable without a request.
 */
final readonly class AuthSessionManager
{
    public function __construct(
        private AuthSessionRepositoryInterface $repository,
        private SessionSettings $settings,
    ) {
    }

    public function settings(): SessionSettings
    {
        return $this->settings;
    }

    /**
     * Opens a session and returns the secret for it — the only moment that secret exists.
     */
    public function start(
        int $userId,
        bool $remember,
        ClientInfoDTO $client,
        ?int $impersonatorId = null,
        ?int $parentSessionId = null,
        ?int $lifetimeOverride = null,
        ?int $now = null,
    ): IssuedSession {
        $now ??= time();
        $token = SecureToken::generate($this->settings->tokenBytes);
        $lifetime = $lifetimeOverride ?? $this->settings->lifetimeFor($remember);

        $session = $this->repository->create(
            [
                'user_id'             => $userId,
                'token_hash'          => SecureToken::hash($token),
                'created_at'          => $now,
                'last_used_at'        => $now,
                'expires_at'          => $now + $lifetime,
                'absolute_expires_at' => $this->absoluteExpiry($now),
                'remember'            => $remember,
                'ip'                  => $client->ip,
                'user_agent'          => mb_substr($client->userAgent, 0, 255),
                'impersonator_id'     => $impersonatorId,
                'parent_session_id'   => $parentSessionId,
            ]
        );

        return new IssuedSession($session, $token);
    }

    /**
     * The session the secret belongs to, or null when there is none that still works.
     */
    public function find(string $token, ?int $now = null): ?AuthSession
    {
        if ($token === '') {
            return null;
        }

        $session = $this->repository->findByTokenHash(SecureToken::hash($token));

        if ($session === null || ! $session->isUsableAt($now ?? time())) {
            return null;
        }

        return $session;
    }

    /**
     * Moves the sliding window forward, counting from this visit rather than from the sign-in:
     * coming back on day 14 pushes the end to day 44.
     *
     * Skipped when the last visit was recent, because extending is a write and a write per
     * request is what this throttle exists to avoid. The comparison is against last_used_at
     * stored on the row, not against anything remembered in this process — otherwise a visitor
     * returning after a fortnight would be judged by a counter that was never set.
     *
     * @return int|null The new expiry when the session was extended, null when it was left
     *                  alone. The caller must reissue the cookie whenever this is not null:
     *                  a row that outlives its cookie signs the visitor out anyway.
     */
    public function touch(AuthSession $session, ?int $now = null): ?int
    {
        $now ??= time();

        if ($session->last_used_at > $now - $this->settings->renewInterval) {
            return null;
        }

        $expiresAt = $now + $this->settings->lifetimeFor($session->remember);

        if ($session->absolute_expires_at !== null) {
            $expiresAt = min($expiresAt, $session->absolute_expires_at);
        }

        $this->repository->extend($session->id, $now, $expiresAt);
        $session->last_used_at = $now;
        $session->expires_at = $expiresAt;

        return $expiresAt;
    }

    /**
     * Replaces the secret of a session that stays the same otherwise: the device keeps its row
     * and its place in "my devices", while the value known before this moment stops working.
     *
     * Done whenever the level of trust changes — signing in, changing a password, entering and
     * leaving impersonation — so a value somebody knew beforehand cannot be used afterwards.
     */
    public function rotate(AuthSession $session, ?int $now = null): IssuedSession
    {
        $now ??= time();
        $token = SecureToken::generate($this->settings->tokenBytes);

        $this->repository->replaceToken($session->id, SecureToken::hash($token));
        $session->token_hash = SecureToken::hash($token);

        $expiresAt = $this->touch($session, $now);

        if ($expiresAt === null) {
            // Rotation is rare and always deliberate, so the throttle that guards the sliding
            // extension must not leave the row untouched here.
            $this->repository->extend($session->id, $now, $session->expires_at);
            $session->last_used_at = $now;
        }

        return new IssuedSession($session, $token);
    }

    public function revoke(AuthSession $session, SessionRevocationReason $reason, ?int $now = null): void
    {
        $now ??= time();

        $this->repository->revoke($session->id, $now, $reason->value);
        $session->revoked_at = $now;
        $session->revoked_reason = $reason->value;
    }

    /**
     * @return int Number of sessions closed.
     */
    public function revokeAllFor(
        int $userId,
        SessionRevocationReason $reason,
        ?int $exceptSessionId = null,
        ?int $now = null,
    ): int {
        return $this->repository->revokeAllForUser($userId, $now ?? time(), $reason->value, $exceptSessionId);
    }

    /**
     * @return Collection<int, AuthSession>
     */
    public function activeFor(int $userId, ?int $now = null): Collection
    {
        return $this->repository->activeForUser($userId, $now ?? time());
    }

    private function absoluteExpiry(int $now): ?int
    {
        return $this->settings->absoluteLifetime === null ? null : $now + $this->settings->absoluteLifetime;
    }
}

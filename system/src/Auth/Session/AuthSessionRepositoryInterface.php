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

interface AuthSessionRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): AuthSession;

    public function findByTokenHash(string $tokenHash): ?AuthSession;

    public function findById(int $id): ?AuthSession;

    /**
     * The sessions to show on the "my devices" screen: still usable, newest activity first.
     * Impersonation sessions are left out — the user must not see an administrator's visit.
     *
     * @return Collection<int, AuthSession>
     */
    public function activeForUser(int $userId, int $now): Collection;

    public function extend(int $id, int $lastUsedAt, int $expiresAt): void;

    public function replaceToken(int $id, string $tokenHash): void;

    public function revoke(int $id, int $revokedAt, string $reason): void;

    /**
     * Revokes every usable session of the user, optionally sparing one — signing out everywhere
     * else without signing out here.
     *
     * @return int Number of sessions revoked.
     */
    public function revokeAllForUser(int $userId, int $revokedAt, string $reason, ?int $exceptId = null): int;

    /**
     * @return int Number of rows removed.
     */
    public function deleteExpiredBefore(int $timestamp): int;

    public function countActiveForUser(int $userId, int $now): int;
}

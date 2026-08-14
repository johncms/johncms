<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Password;

interface PasswordResetTokenRepositoryInterface
{
    public function store(int $userId, string $tokenHash, int $expiresAt, int $createdAt): void;

    /**
     * The token behind the digest, if it is still usable: not spent and not expired at $now.
     */
    public function findUsable(string $tokenHash, int $now): ?PasswordResetToken;

    public function markUsed(int $id, int $usedAt): void;

    /**
     * When this user last asked for a recovery link, spent or not — the rate limit is about
     * how often letters are sent, not about how often they are followed.
     */
    public function lastRequestedAt(int $userId): ?int;

    /**
     * Drops the outstanding requests of one user, so only the newest link ever works.
     */
    public function deleteForUser(int $userId): void;

    /**
     * @return int Number of rows removed.
     */
    public function deleteExpiredBefore(int $timestamp): int;
}

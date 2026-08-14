<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Infrastructure\Persistence\Repository;

use Johncms\Auth\Password\PasswordResetToken;
use Johncms\Auth\Password\PasswordResetTokenRepositoryInterface;

final class EloquentPasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    public function store(int $userId, string $tokenHash, int $expiresAt, int $createdAt): void
    {
        PasswordResetToken::query()->create(
            [
                'user_id'    => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'created_at' => $createdAt,
            ]
        );
    }

    public function findUsable(string $tokenHash, int $now): ?PasswordResetToken
    {
        /** @var PasswordResetToken|null $token Static analysis loses the model type through whereNull(). */
        $token = PasswordResetToken::query()
            ->where('token_hash', '=', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', $now)
            ->first();

        return $token;
    }

    public function markUsed(int $id, int $usedAt): void
    {
        PasswordResetToken::query()->where('id', '=', $id)->update(['used_at' => $usedAt]);
    }

    public function lastRequestedAt(int $userId): ?int
    {
        $latest = PasswordResetToken::query()
            ->where('user_id', '=', $userId)
            ->max('created_at');

        return $latest === null ? null : (int) $latest;
    }

    public function deleteForUser(int $userId): void
    {
        PasswordResetToken::query()->where('user_id', '=', $userId)->delete();
    }

    public function deleteExpiredBefore(int $timestamp): int
    {
        return PasswordResetToken::query()->where('expires_at', '<', $timestamp)->delete();
    }
}

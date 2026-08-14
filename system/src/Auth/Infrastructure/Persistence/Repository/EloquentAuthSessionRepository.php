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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Johncms\Auth\Session\AuthSession;
use Johncms\Auth\Session\AuthSessionRepositoryInterface;

final class EloquentAuthSessionRepository implements AuthSessionRepositoryInterface
{
    public function create(array $attributes): AuthSession
    {
        return AuthSession::query()->create($attributes);
    }

    public function findByTokenHash(string $tokenHash): ?AuthSession
    {
        return AuthSession::query()->where('token_hash', '=', $tokenHash)->first();
    }

    public function findById(int $id): ?AuthSession
    {
        return AuthSession::query()->find($id);
    }

    public function activeForUser(int $userId, int $now): Collection
    {
        /** @var Collection<int, AuthSession> $sessions Static analysis loses the model type through whereNull(). */
        $sessions = $this->usable($now)
            ->where('user_id', '=', $userId)
            ->whereNull('impersonator_id')
            ->orderByDesc('last_used_at')
            ->get();

        return $sessions;
    }

    public function extend(int $id, int $lastUsedAt, int $expiresAt): void
    {
        AuthSession::query()->where('id', '=', $id)->update(
            [
                'last_used_at' => $lastUsedAt,
                'expires_at'   => $expiresAt,
            ]
        );
    }

    public function replaceToken(int $id, string $tokenHash): void
    {
        AuthSession::query()->where('id', '=', $id)->update(['token_hash' => $tokenHash]);
    }

    public function revoke(int $id, int $revokedAt, string $reason): void
    {
        AuthSession::query()->where('id', '=', $id)->whereNull('revoked_at')->update(
            [
                'revoked_at'     => $revokedAt,
                'revoked_reason' => $reason,
            ]
        );
    }

    public function revokeAllForUser(int $userId, int $revokedAt, string $reason, ?int $exceptId = null): int
    {
        $query = $this->usable($revokedAt)->where('user_id', '=', $userId);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->update(
            [
                'revoked_at'     => $revokedAt,
                'revoked_reason' => $reason,
            ]
        );
    }

    public function deleteDeadBefore(int $timestamp): int
    {
        return AuthSession::query()
            ->where('expires_at', '<', $timestamp)
            ->orWhere('revoked_at', '<', $timestamp)
            ->delete();
    }

    public function countActiveForUser(int $userId, int $now): int
    {
        return $this->usable($now)->where('user_id', '=', $userId)->count();
    }

    /**
     * Sessions that can still answer for their user: not signed out, not expired, and inside
     * the absolute cap when one was set.
     *
     * @return Builder<AuthSession>
     */
    private function usable(int $now): Builder
    {
        /** @var Builder<AuthSession> $query Static analysis loses the model type through whereNull(). */
        $query = AuthSession::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', $now)
            ->where(
                static function (Builder $nested) use ($now): void {
                    $nested->whereNull('absolute_expires_at')
                        ->orWhere('absolute_expires_at', '>', $now);
                }
            );

        return $query;
    }
}

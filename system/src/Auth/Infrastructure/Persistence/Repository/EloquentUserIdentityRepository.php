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

use Illuminate\Support\Collection;
use Johncms\Auth\External\UserIdentity;
use Johncms\Auth\External\UserIdentityRepositoryInterface;
use Johncms\Users\User;

final class EloquentUserIdentityRepository implements UserIdentityRepositoryInterface
{
    public function findByProviderUser(string $provider, string $providerUserId): ?UserIdentity
    {
        return UserIdentity::query()
            ->where('provider', '=', $provider)
            ->where('provider_user_id', '=', $providerUserId)
            ->first();
    }

    public function findForUser(int $userId, string $provider): ?UserIdentity
    {
        return UserIdentity::query()
            ->where('user_id', '=', $userId)
            ->where('provider', '=', $provider)
            ->first();
    }

    public function allForUser(int $userId): Collection
    {
        /** @var Collection<int, UserIdentity> $identities */
        $identities = UserIdentity::query()
            ->where('user_id', '=', $userId)
            ->orderBy('provider')
            ->get();

        return $identities;
    }

    public function create(array $attributes): UserIdentity
    {
        return UserIdentity::query()->create($attributes);
    }

    public function touchLogin(int $id, int $timestamp): void
    {
        UserIdentity::query()->where('id', '=', $id)->update(['last_login_at' => $timestamp]);
    }

    public function delete(int $id): void
    {
        UserIdentity::query()->where('id', '=', $id)->delete();
    }

    public function usageOf(string $provider): array
    {
        $userIds = UserIdentity::query()
            ->where('provider', '=', $provider)
            ->pluck('user_id')
            ->all();

        if ($userIds === []) {
            return ['users' => 0, 'without_alternative' => 0];
        }

        // Another way in means either a password or a second provider. Counting it here rather
        // than asking the caller to: the number is the whole point of the warning.
        $withPassword = User::query()
            ->whereIn('id', $userIds)
            ->where('password', '!=', '')
            ->pluck('id')
            ->all();

        $withOtherProvider = UserIdentity::query()
            ->whereIn('user_id', $userIds)
            ->where('provider', '!=', $provider)
            ->pluck('user_id')
            ->all();

        $covered = array_flip(array_merge($withPassword, $withOtherProvider));

        return [
            'users'               => count(array_unique($userIds)),
            'without_alternative' => count(array_filter(
                array_unique($userIds),
                static fn (int $id): bool => ! isset($covered[$id])
            )),
        ];
    }
}

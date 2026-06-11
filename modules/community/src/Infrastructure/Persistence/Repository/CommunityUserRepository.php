<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\Users\User;

final class CommunityUserRepository implements CommunityUserRepositoryInterface
{
    public function countAdministrationUsers(): int
    {
        return User::query()
            ->where('rights', '>', 0)
            ->toBase()
            ->count();
    }

    public function countBirthdayUsers(int $day, int $month): int
    {
        return User::query()
            ->where('dayb', '=', $day)
            ->where('monthb', '=', $month)
            ->where('preg', '=', 1)
            ->toBase()
            ->count();
    }

    public function countApprovedAdministrationUsers(): int
    {
        return User::query()
            ->approved()
            ->where('rights', '>=', 1)
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getApprovedAdministrationUsers(int $limit, int $offset): Collection
    {
        return User::query()
            ->approved()
            ->where('rights', '>=', 1)
            ->orderByDesc('rights')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countApprovedUsers(): int
    {
        return User::query()
            ->approved()
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getApprovedUsers(int $limit, int $offset): Collection
    {
        return User::query()
            ->approved()
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countApprovedBirthdayUsers(int $day, int $month): int
    {
        return User::query()
            ->approved()
            ->where('dayb', '=', $day)
            ->where('monthb', '=', $month)
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getApprovedBirthdayUsers(int $limit, int $offset, int $day, int $month): Collection
    {
        return User::query()
            ->approved()
            ->where('dayb', '=', $day)
            ->where('monthb', '=', $month)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countUsersByLatinNameLike(string $searchLike): int
    {
        return User::query()
            ->approved()
            ->where('name_lat', 'LIKE', $searchLike)
            ->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersByLatinNameLike(int $limit, int $offset, string $searchLike): Collection
    {
        return User::query()
            ->approved()
            ->where('name_lat', 'LIKE', $searchLike)
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getTopForumUsers(int $limit): Collection
    {
        return User::query()
            ->where('postforum', '>', 0)
            ->orderByDesc('postforum')
            ->limit($limit)
            ->get();
    }

    public function getTopGuestbookUsers(int $limit): Collection
    {
        return User::query()
            ->where('postguest', '>', 0)
            ->orderByDesc('postguest')
            ->limit($limit)
            ->get();
    }

    public function getTopCommentUsers(int $limit): Collection
    {
        return User::query()
            ->where('komm', '>', 0)
            ->orderByDesc('komm')
            ->limit($limit)
            ->get();
    }

    public function getTopKarmaUsers(int $limit): Collection
    {
        return User::query()
            ->selectRaw('*, (`karma_plus` - `karma_minus`) as `karma`')
            ->whereRaw('(`karma_plus` - `karma_minus`) > 0')
            ->orderByDesc('karma')
            ->limit($limit)
            ->get();
    }
}

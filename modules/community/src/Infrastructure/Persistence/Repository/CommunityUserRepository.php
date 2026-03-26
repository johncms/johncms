<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\Users\User;

final class CommunityUserRepository implements CommunityUserRepositoryInterface
{
    public function paginateAdministrationUsers(int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->approved()
            ->where('rights', '>=', 1)
            ->orderByDesc('rights')
            ->paginate($perPage);
    }

    public function paginateApprovedUsers(int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->approved()
            ->paginate($perPage);
    }

    public function paginateBirthdayUsers(int $perPage, int $day, int $month): LengthAwarePaginator
    {
        return User::query()
            ->approved()
            ->where('dayb', '=', $day)
            ->where('monthb', '=', $month)
            ->paginate($perPage);
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

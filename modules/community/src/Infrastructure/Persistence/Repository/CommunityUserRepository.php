<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
}

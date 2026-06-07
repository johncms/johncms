<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Users\User;

final class EloquentUserListRepository implements UserListRepositoryInterface
{
    public function paginateApproved(UserListSort $sort, int $page, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->where('preg', 1)
            ->orderBy($sort->column())
            ->paginate($perPage, ['*'], 'page', $page);
    }
}

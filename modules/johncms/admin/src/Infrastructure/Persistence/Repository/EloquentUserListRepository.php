<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Users\User;

final class EloquentUserListRepository implements UserListRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function countApproved(): int
    {
        return User::query()->where('preg', 1)->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getApproved(UserListSort $sort, int $limit, int $offset): Collection
    {
        return User::query()
            ->where('preg', 1)
            ->orderBy($sort->column())
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}

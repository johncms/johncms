<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Users\User;

interface UserListRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * Количество подтверждённых пользователей (preg = 1).
     */
    public function countApproved(): int;

    /**
     * Страница подтверждённых пользователей (preg = 1) с заданной сортировкой.
     *
     * @return Collection<int, User>
     */
    public function getApproved(UserListSort $sort, int $limit, int $offset): Collection;
}

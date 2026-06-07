<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;

interface UserListRepositoryInterface
{
    /**
     * Постраничный список подтверждённых пользователей (preg = 1) с заданной сортировкой.
     *
     * @return LengthAwarePaginator<\Johncms\Users\User>
     */
    public function paginateApproved(UserListSort $sort, int $page, int $perPage): LengthAwarePaginator;
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IpHistoryRepositoryInterface
{
    /**
     * Paginated IP history of the given user, newest first.
     *
     * @return LengthAwarePaginator<int, \Johncms\Users\IpHistory>
     */
    public function paginateByUser(int $userId, int $perPage): LengthAwarePaginator;
}

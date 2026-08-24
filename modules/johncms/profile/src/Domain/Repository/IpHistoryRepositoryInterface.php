<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\IpHistory;

interface IpHistoryRepositoryInterface
{
    /**
     * Count IP history records of the given user.
     */
    public function countByUser(int $userId): int;

    /**
     * A page of the IP history of the given user, newest first.
     *
     * @return Collection<int, IpHistory>
     */
    public function getByUser(int $userId, int $limit, int $offset): Collection;
}

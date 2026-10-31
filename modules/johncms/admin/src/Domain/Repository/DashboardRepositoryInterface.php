<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface DashboardRepositoryInterface
{
    /**
     * The accounts seen since the given moment (by lastdate).
     */
    public function countActiveUsersSince(int $timestamp): int;

    /**
     * The approved accounts registered on each of the days starting at the given second (by datereg).
     *
     * @return list<int> One number per day, oldest first.
     */
    public function countRegisteredUsersByDay(int $start, int $days): array;
}

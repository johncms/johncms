<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\User;

interface IpSearchRepositoryInterface
{
    /**
     * The accounts whose current address, or the one behind their proxy, is in the range.
     */
    public function countUsers(int $from, int $to): int;

    /**
     * The accounts whose current address, or the one behind their proxy, is in the range.
     *
     * @return Collection<int, User>
     */
    public function getUsers(int $from, int $to, int $limit, int $offset): Collection;

    /**
     * The accounts that have ever come from an address in the range, by their history of
     * addresses.
     */
    public function countHistory(int $from, int $to): int;

    /**
     * The accounts that have ever come from an address in the range, the latest first. The
     * address of each, the one behind its proxy and `seen_at` come from the latest record of the
     * history that is in the range.
     *
     * @return Collection<int, User>
     */
    public function getHistory(int $from, int $to, int $limit, int $offset): Collection;
}

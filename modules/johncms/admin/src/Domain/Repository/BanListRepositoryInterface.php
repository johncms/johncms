<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\ValueObjects\BanListFilter;
use Johncms\Users\Ban;
use Johncms\Users\User;

interface BanListRepositoryInterface
{
    /**
     * The accounts the filter lets through.
     *
     * @param int $now Whether a ban is still in force is decided against this moment.
     */
    public function count(BanListFilter $filter, int $now): int;

    /**
     * A page of the accounts, each with the attributes `ban_id` (the ban of it that ends last,
     * of the kind of the filter if it has one), `bantime` (when that ban ends) and `bancount`
     * (all the bans of the account).
     *
     * @return Collection<int, User>
     */
    public function get(BanListFilter $filter, int $now, int $limit, int $offset): Collection;

    /**
     * @param list<int> $ids
     * @return array<int, Ban> Keyed by the id.
     */
    public function bansByIds(array $ids): array;
}

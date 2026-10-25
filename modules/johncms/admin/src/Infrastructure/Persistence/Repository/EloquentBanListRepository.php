<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Johncms\Modules\Admin\Domain\Enums\BanListStatus;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;
use Johncms\Modules\Admin\Domain\ValueObjects\BanListFilter;
use Johncms\Users\Ban;
use Johncms\Users\User;

/**
 * One row per account, the ban and the counts as columns of their own: a join on the time a ban
 * ends would repeat an account whose two bans end at the same moment — and the amnesty ends
 * every ban in force at one moment.
 */
final class EloquentBanListRepository implements BanListRepositoryInterface
{
    public function count(BanListFilter $filter, int $now): int
    {
        return $this->query($filter, $now)->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function get(BanListFilter $filter, int $now, int $limit, int $offset): Collection
    {
        return $this->query($filter, $now)
            ->select('users.*')
            ->selectSub($this->bansOf($filter)->selectRaw('MAX(ban_time)'), 'bantime')
            ->selectSub($this->bansOf($filter)->select('id')->orderByDesc('ban_time')->orderByDesc('id')->limit(1), 'ban_id')
            ->selectSub(Capsule::table('cms_ban_users')->selectRaw('COUNT(*)')->whereColumn('cms_ban_users.user_id', 'users.id'), 'bancount')
            ->orderByDesc($filter->sort->column())
            ->orderByDesc('users.id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function bansByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var array<int, Ban> $bans */
        $bans = Ban::query()->whereIn('id', $ids)->get()->keyBy('id')->all();

        return $bans;
    }

    /**
     * @return Builder<User>
     */
    private function query(BanListFilter $filter, int $now): Builder
    {
        $bans = $this->bansOf($filter);
        if ($filter->status === BanListStatus::ACTIVE) {
            $bans->where('ban_time', '>', $now);
        }

        $query = User::query();
        $query->whereExists($bans);

        if ($filter->search !== '') {
            $search = $filter->search;
            $query->where(
                static function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%');

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                }
            );
        }

        return $query;
    }

    /**
     * The bans of the account the outer query is looking at, of the kind of the filter.
     */
    private function bansOf(BanListFilter $filter): QueryBuilder
    {
        $query = Capsule::table('cms_ban_users')->whereColumn('cms_ban_users.user_id', 'users.id');

        if ($filter->kind !== null) {
            $query->where('ban_type', '=', $filter->kind->value);
        }

        return $query;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Johncms\Modules\Admin\Domain\Repository\IpSearchRepositoryInterface;
use Johncms\Users\User;

final class EloquentIpSearchRepository implements IpSearchRepositoryInterface
{
    public function countUsers(int $from, int $to): int
    {
        return $this->usersQuery($from, $to)->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(int $from, int $to, int $limit, int $offset): Collection
    {
        return $this->usersQuery($from, $to)
            ->orderBy('ip')
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countHistory(int $from, int $to): int
    {
        return $this->historyQuery($from, $to)->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function getHistory(int $from, int $to, int $limit, int $offset): Collection
    {
        // The columns of the record go after `users.*` under the same names, so the address the
        // row shows is the one from the history.
        return $this->historyQuery($from, $to)
            ->select('users.*')
            ->selectSub($this->latestRecord($from, $to)->select('ip'), 'ip')
            ->selectSub($this->latestRecord($from, $to)->select('ip_via_proxy'), 'ip_via_proxy')
            ->selectSub($this->latestRecord($from, $to)->select('time'), 'seen_at')
            ->orderByDesc('seen_at')
            ->orderBy('users.name')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<User>
     */
    private function usersQuery(int $from, int $to): Builder
    {
        return User::query()
            ->where(static function ($query) use ($from, $to): void {
                $query->whereBetween('ip', [$from, $to])
                    ->orWhereBetween('ip_via_proxy', [$from, $to]);
            });
    }

    /**
     * Any record of the history in the range finds the account: an address used once and left
     * since is what this search is for.
     *
     * @return Builder<User>
     */
    private function historyQuery(int $from, int $to): Builder
    {
        $query = User::query();
        $query->whereExists($this->recordsInRange($from, $to));

        return $query;
    }

    /**
     * The records of the history of the account the outer query is looking at, in the range.
     */
    private function recordsInRange(int $from, int $to): QueryBuilder
    {
        return Capsule::table('cms_users_iphistory')
            ->whereColumn('cms_users_iphistory.user_id', 'users.id')
            ->where(static function (QueryBuilder $query) use ($from, $to): void {
                $query->whereBetween('ip', [$from, $to])
                    ->orWhereBetween('ip_via_proxy', [$from, $to]);
            });
    }

    private function latestRecord(int $from, int $to): QueryBuilder
    {
        return $this->recordsInRange($from, $to)->orderByDesc('time')->orderByDesc('id')->limit(1);
    }
}

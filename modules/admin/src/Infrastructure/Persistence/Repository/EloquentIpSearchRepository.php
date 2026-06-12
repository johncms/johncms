<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
        // Подставляем IP из исторической записи в атрибуты модели пользователя
        // (alias идёт после `users.*`, поэтому перекрывает текущий IP при выборке).
        return $this->historyQuery($from, $to)
            ->select('users.*')
            ->addSelect([
                Capsule::raw('hst.ip as ip'),
                Capsule::raw('hst.ip_via_proxy as ip_via_proxy'),
            ])
            ->orderByDesc('hst.time')
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
     * @return Builder<User>
     */
    private function historyQuery(int $from, int $to): Builder
    {
        // Последняя по времени запись истории для каждого пользователя.
        $latest = Capsule::table('cms_users_iphistory')
            ->select('user_id', Capsule::raw('MAX(`time`) as mtime'))
            ->groupBy('user_id');

        return User::query()
            ->join('cms_users_iphistory as hst', 'hst.user_id', '=', 'users.id')
            ->joinSub($latest, 't', static function ($join): void {
                $join->on('t.mtime', '=', 'hst.time')
                    ->on('t.user_id', '=', 'hst.user_id');
            })
            ->where(static function ($query) use ($from, $to): void {
                $query->whereBetween('hst.ip', [$from, $to])
                    ->orWhereBetween('hst.ip_via_proxy', [$from, $to]);
            });
    }
}

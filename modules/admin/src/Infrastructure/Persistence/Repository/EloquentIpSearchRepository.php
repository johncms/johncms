<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Admin\Domain\Repository\IpSearchRepositoryInterface;
use Johncms\Users\User;

final class EloquentIpSearchRepository implements IpSearchRepositoryInterface
{
    public function paginateUsers(int $from, int $to, int $page, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->where(static function ($query) use ($from, $to): void {
                $query->whereBetween('ip', [$from, $to])
                    ->orWhereBetween('ip_via_proxy', [$from, $to]);
            })
            ->orderBy('ip')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function paginateHistory(int $from, int $to, int $page, int $perPage): LengthAwarePaginator
    {
        // Последняя по времени запись истории для каждого пользователя.
        $latest = Capsule::table('cms_users_iphistory')
            ->select('user_id', Capsule::raw('MAX(`time`) as mtime'))
            ->groupBy('user_id');

        // Подставляем IP из исторической записи в атрибуты модели пользователя
        // (alias идёт после `users.*`, поэтому перекрывает текущий IP при выборке).
        return User::query()
            ->select('users.*')
            ->addSelect([
                Capsule::raw('hst.ip as ip'),
                Capsule::raw('hst.ip_via_proxy as ip_via_proxy'),
            ])
            ->join('cms_users_iphistory as hst', 'hst.user_id', '=', 'users.id')
            ->joinSub($latest, 't', static function ($join): void {
                $join->on('t.mtime', '=', 'hst.time')
                    ->on('t.user_id', '=', 'hst.user_id');
            })
            ->where(static function ($query) use ($from, $to): void {
                $query->whereBetween('hst.ip', [$from, $to])
                    ->orWhereBetween('hst.ip_via_proxy', [$from, $to]);
            })
            ->orderByDesc('hst.time')
            ->orderBy('users.name')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}

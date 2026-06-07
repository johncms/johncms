<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Modules\Admin\Domain\Repository\BanListRepositoryInterface;
use Johncms\Users\User;

final class EloquentBanListRepository implements BanListRepositoryInterface
{
    public function paginate(BanListSort $sort, int $page, int $perPage): LengthAwarePaginator
    {
        // Последняя по времени запись бана для каждого пользователя.
        $latest = Capsule::table('cms_ban_users')
            ->select('user_id', Capsule::raw('MAX(`ban_time`) as mtime'))
            ->groupBy('user_id');

        return User::query()
            ->select('users.*')
            ->addSelect([
                Capsule::raw('ban.id as ban_id'),
                Capsule::raw('ban.ban_time as bantime'),
                Capsule::raw('(SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = users.id) as bancount'),
            ])
            ->join('cms_ban_users as ban', 'ban.user_id', '=', 'users.id')
            ->joinSub($latest, 'tmp', static function ($join): void {
                $join->on('tmp.user_id', '=', 'ban.user_id')
                    ->on('tmp.mtime', '=', 'ban.ban_time');
            })
            ->orderByDesc($sort->column())
            ->paginate($perPage, ['*'], 'page', $page);
    }
}

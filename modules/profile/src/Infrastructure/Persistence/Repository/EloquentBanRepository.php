<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Profile\Domain\Repository\BanRepositoryInterface;
use Johncms\Users\Ban;

final class EloquentBanRepository implements BanRepositoryInterface
{
    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return Ban::query()
            ->where('user_id', '=', $userId)
            ->orderByDesc('ban_time')
            ->paginate($perPage);
    }

    public function countActiveByType(int $userId, int $type): int
    {
        return Ban::query()
            ->where('user_id', '=', $userId)
            ->where('ban_time', '>', time())
            ->where('ban_type', '=', $type)
            ->count();
    }

    public function findForUser(int $banId, int $userId): ?Ban
    {
        return Ban::query()
            ->where('id', '=', $banId)
            ->where('user_id', '=', $userId)
            ->first();
    }

    public function add(int $userId, int $banTime, int $banWhile, int $banType, string $banWho, string $banReason): void
    {
        Ban::query()->create([
            'user_id'    => $userId,
            'ban_time'   => $banTime,
            'ban_while'  => $banWhile,
            'ban_type'   => $banType,
            'ban_who'    => $banWho,
            'ban_reason' => $banReason,
        ]);
    }

    public function terminate(int $banId, int $time): void
    {
        Ban::query()->where('id', '=', $banId)->update(['ban_time' => $time]);
    }

    public function deleteById(int $banId): void
    {
        Ban::query()->where('id', '=', $banId)->delete();
    }

    public function deleteAllForUser(int $userId): void
    {
        Ban::query()->where('user_id', '=', $userId)->delete();
    }
}

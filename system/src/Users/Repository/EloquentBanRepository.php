<?php

declare(strict_types=1);

namespace Johncms\Users\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\Ban;
use Johncms\Users\Karma;
use Johncms\Users\User;

final class EloquentBanRepository implements BanRepositoryInterface
{
    public function countForUser(int $userId): int
    {
        return Ban::query()->where('user_id', '=', $userId)->count();
    }

    /**
     * @return Collection<int, Ban>
     */
    public function getForUser(int $userId, int $limit, int $offset): Collection
    {
        return Ban::query()
            ->where('user_id', '=', $userId)
            ->orderByDesc('ban_time')
            ->offset($offset)
            ->limit($limit)
            ->get();
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

    /**
     * @return Collection<int, Ban>
     */
    public function getFinishedForUser(int $userId, int $time): Collection
    {
        return Ban::query()
            ->where('user_id', '=', $userId)
            ->where('ban_time', '<=', $time)
            ->get();
    }

    /**
     * By the id, so deleting the bans already read does not shift the portions still to come.
     *
     * @return iterable<Ban>
     */
    public function eachBan(): iterable
    {
        return Ban::query()->lazyById(200);
    }

    public function addKarmaPenalty(int $userId, int $points, int $time, string $voterName, string $reason): void
    {
        Karma::query()->create([
            'user_id'    => 0,
            'name'       => $voterName,
            'karma_user' => $userId,
            'points'     => $points,
            'type'       => 0,
            'time'       => $time,
            'text'       => $reason,
        ]);
        User::query()->where('id', $userId)->increment('karma_minus', $points);
    }

    public function findKarmaPenalty(int $userId, int $time): ?Karma
    {
        return Karma::query()
            ->where('karma_user', $userId)
            ->where('user_id', 0)
            ->where('time', $time)
            ->first();
    }

    public function removeKarmaPenalty(Karma $penalty): void
    {
        Karma::query()->where('id', $penalty->id)->delete();

        $user = User::query()->find($penalty->karma_user);
        if ($user !== null) {
            $user->karma_minus = max(0, (int) $user->karma_minus - (int) $penalty->points);
            $user->save();
        }
    }
}

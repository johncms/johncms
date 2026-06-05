<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Users\Karma;

final class EloquentKarmaRepository implements KarmaRepositoryInterface
{
    public function sumPointsGivenSince(int $voterId, int $sinceTime): int
    {
        return (int) Karma::query()
            ->where('user_id', '=', $voterId)
            ->where('time', '>=', $sinceTime)
            ->sum('points');
    }

    public function countVotesGivenTo(int $voterId, int $targetId, int $sinceTime, int $afterTime): int
    {
        return Karma::query()
            ->where('user_id', '=', $voterId)
            ->where('time', '>=', $sinceTime)
            ->where('karma_user', '=', $targetId)
            ->where('time', '>', $afterTime)
            ->count();
    }

    public function countVotesReceivedAfter(int $targetId, int $afterTime): int
    {
        return Karma::query()
            ->where('karma_user', '=', $targetId)
            ->where('time', '>', $afterTime)
            ->count();
    }

    public function paginateReceived(int $targetId, ?int $type, int $perPage): LengthAwarePaginator
    {
        return Karma::query()
            ->where('karma_user', '=', $targetId)
            ->when($type !== null, static fn ($query) => $query->where('type', '=', $type))
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    public function paginateReceivedAfter(int $targetId, int $afterTime, int $perPage): LengthAwarePaginator
    {
        return Karma::query()
            ->where('karma_user', '=', $targetId)
            ->where('time', '>', $afterTime)
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    public function addVote(int $voterId, string $voterName, int $targetId, int $points, int $type, int $time, string $text): void
    {
        Karma::query()->create([
            'user_id'    => $voterId,
            'name'       => $voterName,
            'karma_user' => $targetId,
            'points'     => $points,
            'type'       => $type,
            'time'       => $time,
            'text'       => $text,
        ]);
    }

    public function findVote(int $voteId, int $targetId): ?Karma
    {
        return Karma::query()
            ->where('id', '=', $voteId)
            ->where('karma_user', '=', $targetId)
            ->first();
    }

    public function deleteVote(int $voteId): void
    {
        Karma::query()->where('id', '=', $voteId)->delete();
    }

    public function deleteAllForTarget(int $targetId): void
    {
        Karma::query()->where('karma_user', '=', $targetId)->delete();
    }

    public function deleteSystemPenalty(int $targetId, int $time): void
    {
        Karma::query()
            ->where('karma_user', '=', $targetId)
            ->where('user_id', '=', 0)
            ->where('time', '=', $time)
            ->limit(1)
            ->delete();
    }
}

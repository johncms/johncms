<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

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
}

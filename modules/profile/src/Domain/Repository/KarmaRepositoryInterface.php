<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

interface KarmaRepositoryInterface
{
    /**
     * Sum of points the voter has given since the specified time (inclusive).
     */
    public function sumPointsGivenSince(int $voterId, int $sinceTime): int;

    /**
     * Count votes the voter has given to the target user within the time window.
     */
    public function countVotesGivenTo(int $voterId, int $targetId, int $sinceTime, int $afterTime): int;

    /**
     * Count votes the target user has received after the specified time.
     */
    public function countVotesReceivedAfter(int $targetId, int $afterTime): int;
}

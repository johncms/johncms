<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Users\Karma;

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

    /**
     * Paginate votes received by the target user, optionally filtered by type (null = all types).
     *
     * @return LengthAwarePaginator<Karma>
     */
    public function paginateReceived(int $targetId, ?int $type, int $perPage): LengthAwarePaginator;

    /**
     * Paginate votes received by the target user after the specified time.
     *
     * @return LengthAwarePaginator<Karma>
     */
    public function paginateReceivedAfter(int $targetId, int $afterTime, int $perPage): LengthAwarePaginator;

    /**
     * Insert a new vote record.
     */
    public function addVote(int $voterId, string $voterName, int $targetId, int $points, int $type, int $time, string $text): void;

    /**
     * Find a single vote by its id that belongs to the target user.
     */
    public function findVote(int $voteId, int $targetId): ?Karma;

    /**
     * Delete a single vote by its id.
     */
    public function deleteVote(int $voteId): void;

    /**
     * Delete all votes received by the target user.
     */
    public function deleteAllForTarget(int $targetId): void;
}

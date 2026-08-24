<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
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
     * Count votes received by the target user, optionally filtered by type (null = all types).
     */
    public function countReceived(int $targetId, ?int $type): int;

    /**
     * A page of votes received by the target user, optionally filtered by type (null = all types), newest first.
     *
     * @return Collection<int, Karma>
     */
    public function getReceived(int $targetId, ?int $type, int $limit, int $offset): Collection;

    /**
     * A page of votes received by the target user after the specified time, newest first.
     *
     * @return Collection<int, Karma>
     */
    public function getReceivedAfter(int $targetId, int $afterTime, int $limit, int $offset): Collection;

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

    /**
     * Delete a single system penalty record (user_id = 0) added to the target user at the given time.
     */
    public function deleteSystemPenalty(int $targetId, int $time): void;
}

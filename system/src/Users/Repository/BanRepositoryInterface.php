<?php

declare(strict_types=1);

namespace Johncms\Users\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\Ban;
use Johncms\Users\Karma;

interface BanRepositoryInterface
{
    /**
     * Count the records in the user's violation history.
     */
    public function countForUser(int $userId): int;

    /**
     * A page of the user's violation history (most recent first).
     *
     * @return Collection<int, Ban>
     */
    public function getForUser(int $userId, int $limit, int $offset): Collection;

    /**
     * Count the user's currently active bans of the given type.
     */
    public function countActiveByType(int $userId, int $type): int;

    /**
     * Find a single ban by its id that belongs to the user.
     */
    public function findForUser(int $banId, int $userId): ?Ban;

    /**
     * Insert a new ban record.
     */
    public function add(int $userId, int $banTime, int $banWhile, int $banType, string $banWho, string $banReason): void;

    /**
     * Terminate an active ban while keeping it in the history (set the end time to now).
     */
    public function terminate(int $banId, int $time): void;

    /**
     * Delete a single ban record by its id.
     */
    public function deleteById(int $banId): void;

    /**
     * The bans of the user that have run out or were lifted by the given time.
     *
     * @return Collection<int, Ban>
     */
    public function getFinishedForUser(int $userId, int $time): Collection;

    /**
     * Every ban of every account, read a portion at a time, so a site with a long history does not
     * load it whole.
     *
     * @return iterable<Ban>
     */
    public function eachBan(): iterable;

    /**
     * The karma a ban costs: a vote of the system against the account, and its points taken
     * from the karma of the account.
     */
    public function addKarmaPenalty(int $userId, int $points, int $time, string $voterName, string $reason): void;

    /**
     * The penalty a ban given at that moment cost, if it cost one.
     */
    public function findKarmaPenalty(int $userId, int $time): ?Karma;

    /**
     * Takes a penalty back: the vote goes, and its points come back to the account, never below
     * nothing.
     */
    public function removeKarmaPenalty(Karma $penalty): void;
}

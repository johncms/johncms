<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\Ban;

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
     * Delete the whole violation history of the user.
     */
    public function deleteAllForUser(int $userId): void;
}

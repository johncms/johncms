<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Users\Ban;

interface BanRepositoryInterface
{
    /**
     * Paginate the user's violation history (most recent first).
     *
     * @return LengthAwarePaginator<Ban>
     */
    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator;

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

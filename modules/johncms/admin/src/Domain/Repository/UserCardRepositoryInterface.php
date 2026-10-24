<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\IpHistory;
use Johncms\Users\User;

/**
 * What the page of an account shows beside the account itself.
 */
interface UserCardRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * The addresses the account came from before the current one, the latest first.
     *
     * @return Collection<int, IpHistory>
     */
    public function getRecentIps(int $userId, int $limit): Collection;

    public function countIps(int $userId): int;

    /**
     * The bans in force on the account: the kind of each and when it ends.
     *
     * @return array<int, int> Kind => the end of the ban, a timestamp.
     */
    public function getActiveBans(int $userId, int $now): array;

    /**
     * How many bans the account has had, those in force included.
     */
    public function countBans(int $userId): int;
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Events\AuthEvent;

/**
 * The numbers behind the security block of the dashboard: refused sign-ins, the staff signing in,
 * the bans.
 */
interface SecurityOverviewRepositoryInterface
{
    public function countFailedSignInsSince(int $since): int;

    /**
     * The addresses refused most often since the given moment, the busiest first.
     *
     * @return array<string, int> The number of refused attempts by address.
     */
    public function getTopFailedIps(int $since, int $limit): array;

    /**
     * The latest sign-ins of the accounts holding a role that has not run out, the newest first,
     * each with the name of the account as user_name.
     *
     * @return Collection<int, AuthEvent>
     */
    public function getStaffSignIns(int $now, int $limit): Collection;

    /**
     * How many addresses were refused at least the given number of times since the given moment.
     */
    public function countIpsWithFailedSignInsSince(int $since, int $atLeast): int;

    public function countActiveBans(int $now): int;

    public function countBansIssuedSince(int $since): int;
}

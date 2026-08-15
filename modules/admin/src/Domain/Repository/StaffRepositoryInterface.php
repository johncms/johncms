<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Users\User;

interface StaffRepositoryInterface
{
    /**
     * Accounts holding at least one role that was granted to them, ordered by name.
     *
     * The default role is not among them: everybody signed in holds it, and it is applied without
     * a row of its own.
     *
     * @return Collection<int, User>
     */
    public function getStaff(int $now): Collection;

    /**
     * Every grant still in force, so the list can be grouped by role.
     *
     * @return Collection<int, UserRole>
     */
    public function getRoleAssignments(int $now): Collection;

    /**
     * How many accounts hold a granted role at all.
     */
    public function countStaff(int $now): int;
}

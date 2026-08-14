<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use Johncms\Users\User;

/**
 * Keeps users.rights in step with the roles an account holds.
 *
 * Three hundred checks across the modules and the templates still compare against that number,
 * and they cannot all be rewritten in one commit without leaving the site broken in between. So
 * the number stays, computed from the roles rather than being the source of truth: granting a
 * role updates it, and every one of those checks goes on working while they are converted one
 * module at a time.
 *
 * It disappears with the column, once nothing reads it.
 */
final readonly class RightsMirror
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    /**
     * Recomputes the number for one account and stores it.
     *
     * @return int The value written.
     */
    public function sync(int $userId, ?int $now = null): int
    {
        $rights = $this->rightsFor($userId, $now ?? time());

        User::query()->where('id', '=', $userId)->update(['rights' => $rights]);

        return $rights;
    }

    /**
     * The number the account's roles add up to: the highest of them, since the old scale was a
     * ladder and a role without a rung on it contributes nothing.
     */
    public function rightsFor(int $userId, ?int $now = null): int
    {
        $rights = 0;

        foreach ($this->roles->forUser($userId, $now ?? time()) as $role) {
            $rights = max($rights, $role->legacy_rights ?? 0);
        }

        return $rights;
    }
}

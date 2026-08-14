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
use RuntimeException;

/**
 * Turns the numeric users.rights of an existing site into role assignments.
 *
 * Only accounts above zero get a row: the role everybody has is applied without one, so a site
 * with a hundred thousand accounts comes out of this with a handful of rows rather than a
 * hundred thousand.
 */
final readonly class LegacyRightsMigration
{
    public function __construct(
        private RoleRepositoryInterface $roles,
    ) {
    }

    /**
     * @param bool $reset When false, accounts that already have roles are left alone — somebody
     *                    may have arranged them by hand after the first run, and a second run
     *                    must not undo that.
     *
     * @return LegacyRightsMigrationReport
     */
    public function migrate(bool $reset = false, ?int $now = null): LegacyRightsMigrationReport
    {
        $now ??= time();
        $granted = 0;
        $skipped = 0;
        /** @var array<int, list<int>> $unrecognised Account ids per unknown rights value. */
        $unrecognised = [];

        foreach ($this->staffAccounts() as $account) {
            $userId = (int) $account->id;
            $rights = (int) $account->rights;

            if (! $reset && $this->roles->grantedTo($userId, $now)->isNotEmpty()) {
                ++$skipped;
                continue;
            }

            $systemRole = SystemRole::fromLegacyRights($rights);

            if ($systemRole === null) {
                // Values nobody documented — 1, 2, 8. Checks like `rights >= 1` gave them meaning,
                // so they cannot be dropped; they map down to the nearest role, which never grants
                // more than the account had, and every one of them is reported.
                $systemRole = SystemRole::nearestBelowLegacyRights($rights);
                $unrecognised[$rights][] = $userId;
            }

            $role = $this->roles->findBySlug($systemRole->value)
                ?? throw new RuntimeException(
                    sprintf('The "%s" role is missing. Run auth:upgrade-schema first.', $systemRole->value)
                );

            // The default role is already applied to everyone; a row for it would say nothing.
            if (! $role->is_default) {
                $this->roles->grant($userId, $role->id, null, $now);
                ++$granted;
            }
        }

        return new LegacyRightsMigrationReport($granted, $skipped, $unrecognised);
    }

    /**
     * The accounts the numeric column says something about. Everyone else is an ordinary user
     * and needs no row.
     *
     * @return iterable<object{id: int, rights: int}>
     */
    private function staffAccounts(): iterable
    {
        return User::query()
            ->where('rights', '>', 0)
            ->orderBy('id')
            ->get(['id', 'rights'])
            ->all();
    }

    /**
     * A snapshot of what the column said, taken before anything is written. RightsMirror
     * recomputes the number from the roles afterwards, so an undocumented value such as 8 is
     * rewritten as 7 and is otherwise unrecoverable.
     *
     * @return array<int, int> Account id to rights.
     */
    public function snapshot(): array
    {
        $snapshot = [];

        foreach ($this->staffAccounts() as $account) {
            $snapshot[(int) $account->id] = (int) $account->rights;
        }

        return $snapshot;
    }
}

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

use Symfony\Contracts\Service\ResetInterface;

/**
 * What an account is called next to its nickname, and whether it is one of the staff at all.
 *
 * Both used to be read off users.rights: a number above zero was staff, and a table of six
 * numbers gave the caption. The answer is the highest role granted to the account — the roles
 * everybody carries by default say nothing about anybody, so an account with none has no title
 * and is not staff.
 *
 * Answers are kept for the length of the request: a listing asks about the same author on every
 * row of theirs, and preload() gets a whole page in one query.
 */
final class StaffTitles implements ResetInterface
{
    /** @var array<int, Role|null> Highest granted role per account; null when there is none. */
    private array $roles = [];

    public function __construct(private readonly RoleRepositoryInterface $repository)
    {
    }

    /**
     * The caption under the nickname; an empty string for an account without a granted role.
     */
    public function titleFor(int $userId): string
    {
        $role = $this->roleOf($userId);

        return $role instanceof Role ? $role->display_name : '';
    }

    /**
     * Whether the account holds a role of its own. What "the smilies of the staff" and the
     * markers next to a nickname used to ask the number for.
     */
    public function isStaff(int $userId): bool
    {
        return $this->roleOf($userId) !== null;
    }

    /**
     * Loads a whole page of accounts at once, so the rows that follow answer without a query.
     *
     * @param list<int> $userIds
     */
    public function preload(array $userIds): void
    {
        $missing = array_values(array_filter(
            array_unique($userIds),
            fn (int $userId): bool => $userId > 0 && ! array_key_exists($userId, $this->roles)
        ));

        if ($missing === []) {
            return;
        }

        $granted = $this->repository->grantedRolesFor($missing, time());

        foreach ($missing as $userId) {
            $this->roles[$userId] = $this->highest($granted[$userId] ?? []);
        }
    }

    public function reset(): void
    {
        $this->roles = [];
    }

    private function roleOf(int $userId): ?Role
    {
        if ($userId <= 0) {
            return null;
        }

        if (! array_key_exists($userId, $this->roles)) {
            $this->preload([$userId]);
        }

        return $this->roles[$userId];
    }

    /**
     * @param list<Role> $roles
     */
    private function highest(array $roles): ?Role
    {
        $highest = null;

        foreach ($roles as $role) {
            if ($highest === null || $role->level > $highest->level) {
                $highest = $role;
            }
        }

        return $highest;
    }
}

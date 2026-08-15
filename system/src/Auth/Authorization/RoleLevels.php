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

use Johncms\Auth\Identity;

/**
 * Where a visitor stands in the hierarchy of roles.
 *
 * The level answers the questions permissions cannot: who outranks whom. It is what keeps the
 * role editor from being a way up — an administrator may hand out what they hold, not what
 * stands above them — and later decides who may moderate or browse as whom.
 */
final readonly class RoleLevels
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    /**
     * The highest level of the roles the visitor holds; zero for a guest.
     */
    public function highest(Identity $identity): int
    {
        $level = 0;

        foreach ($identity->roles as $slug) {
            $level = max($level, $this->levelOf($slug));
        }

        return $level;
    }

    /**
     * The highest level of the roles one account holds, whether granted or applied by default.
     *
     * This is the number the admin panel compares against: nobody may change the roles of somebody
     * standing above them.
     */
    public function highestGrantedTo(int $userId, ?int $now = null): int
    {
        $level = 0;

        foreach ($this->roles->forUser($userId, $now ?? time()) as $role) {
            $level = max($level, $role->level);
        }

        return $level;
    }

    /**
     * The same for a whole page of accounts, in one query. What a listing asks before deciding
     * which of its rows carry the buttons of the staff.
     *
     * @param list<int> $userIds
     * @return array<int, int> User id => level, one entry per account asked for.
     */
    public function highestGrantedToMany(array $userIds, ?int $now = null): array
    {
        if ($userIds === []) {
            return [];
        }

        // Whatever the default roles carry is the floor under every account, granted or not.
        $floor = 0;

        foreach ($this->roles->defaults() as $role) {
            $floor = max($floor, $role->level);
        }

        $granted = $this->roles->grantedLevelsFor(array_values(array_unique($userIds)), $now ?? time());

        $levels = [];

        foreach ($userIds as $userId) {
            $levels[$userId] = max($floor, $granted[$userId] ?? 0);
        }

        return $levels;
    }

    /**
     * The built-in roles answer without a query; one the site added is looked up.
     */
    public function levelOf(string $slug): int
    {
        $systemRole = SystemRole::tryFrom($slug);

        if ($systemRole !== null) {
            return $systemRole->level();
        }

        return $this->roles->findBySlug($slug)->level ?? 0;
    }
}

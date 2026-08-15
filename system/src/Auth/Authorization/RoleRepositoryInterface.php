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

use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection;

    public function findBySlug(string $slug): ?Role;

    public function findById(int $id): ?Role;

    /**
     * The roles that apply to a signed-in visitor with no rows of their own.
     *
     * @return Collection<int, Role>
     */
    public function defaults(): Collection;

    public function guestRole(): ?Role;

    /**
     * The roles of one account: the default ones plus whatever was granted to them and has not
     * expired.
     *
     * @return Collection<int, Role>
     */
    public function forUser(int $userId, int $now): Collection;

    /**
     * The permission keys and patterns the given roles carry.
     *
     * @param list<int> $roleIds
     * @return list<string>
     */
    public function permissionsFor(array $roleIds): array;

    /**
     * How many permissions each role carries, keyed by role id.
     *
     * @return array<int, int>
     */
    public function permissionCounts(): array;

    /**
     * How many accounts hold each role, keyed by role id. Only the granted ones are counted: a
     * default role has no rows and applies to everybody signed in.
     *
     * @return array<int, int>
     */
    public function holderCounts(int $now): array;

    public function create(string $slug, string $name, int $level, int $now): Role;

    /**
     * Changes what a role is called and where it sits in the hierarchy. The slug is not among the
     * arguments on purpose: code refers to it, so it is fixed once the role exists.
     */
    public function update(int $roleId, string $name, int $level, int $now): void;

    /**
     * Removes the role together with its permissions and everything granted through it.
     */
    public function delete(int $roleId): void;

    /**
     * Replaces the permissions of a role with the given set.
     *
     * @param list<string> $permissions
     */
    public function setPermissions(int $roleId, array $permissions): void;

    /**
     * The explicit grants of one account: role id => when the grant runs out, null when it never
     * does. Expired rows are included, because the screen listing them is the one that clears them.
     *
     * @return array<int, int|null>
     */
    public function grantsFor(int $userId): array;

    public function grant(int $userId, int $roleId, ?int $grantedBy, int $grantedAt, ?int $expiresAt = null): void;

    public function revoke(int $userId, int $roleId): void;

    /**
     * @return Collection<int, Role>
     */
    public function grantedTo(int $userId, int $now): Collection;

    /**
     * The highest level granted to each of these accounts, in one query rather than one per
     * account: a listing comparing the visitor against every author on the page would otherwise
     * ask the same question a dozen times.
     *
     * Accounts holding nothing beyond the default roles are absent from the result.
     *
     * @param list<int> $userIds
     * @return array<int, int> User id => level.
     */
    public function grantedLevelsFor(array $userIds, int $now): array;

    /**
     * The roles granted to each of these accounts, in one query. Accounts holding none are
     * absent from the result, and so are the roles every account has by default: what is asked
     * here is what distinguishes an account, not what everybody carries.
     *
     * @param list<int> $userIds
     * @return array<int, list<Role>> User id => roles.
     */
    public function grantedRolesFor(array $userIds, int $now): array;
}

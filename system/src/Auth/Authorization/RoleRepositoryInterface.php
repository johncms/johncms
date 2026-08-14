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
     * Replaces the permissions of a role with the given set.
     *
     * @param list<string> $permissions
     */
    public function setPermissions(int $roleId, array $permissions): void;

    public function grant(int $userId, int $roleId, ?int $grantedBy, int $grantedAt, ?int $expiresAt = null): void;

    public function revoke(int $userId, int $roleId): void;

    /**
     * @return Collection<int, Role>
     */
    public function grantedTo(int $userId, int $now): Collection;
}

<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RolePermission;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Authorization\UserRole;

final class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function all(): Collection
    {
        /** @var Collection<int, Role> $roles Static analysis loses the model type through orderByDesc(). */
        $roles = Role::query()->orderByDesc('level')->orderBy('id')->get();

        return $roles;
    }

    public function findBySlug(string $slug): ?Role
    {
        return Role::query()->where('slug', '=', $slug)->first();
    }

    public function findById(int $id): ?Role
    {
        return Role::query()->find($id);
    }

    public function defaults(): Collection
    {
        return Role::query()->where('is_default', '=', true)->get();
    }

    public function guestRole(): ?Role
    {
        return Role::query()->where('is_guest', '=', true)->first();
    }

    public function forUser(int $userId, int $now): Collection
    {
        return $this->defaults()
            ->merge($this->grantedTo($userId, $now))
            ->unique('id')
            ->values();
    }

    public function grantedTo(int $userId, int $now): Collection
    {
        /** @var Collection<int, Role> $roles Static analysis loses the model type through whereNull(). */
        $roles = Role::query()
            ->join(UserRole::query()->getModel()->getTable() . ' as ur', 'ur.role_id', '=', 'roles.id')
            ->where('ur.user_id', '=', $userId)
            ->where(
                static function (Builder $query) use ($now): void {
                    $query->whereNull('ur.expires_at')->orWhere('ur.expires_at', '>', $now);
                }
            )
            ->select('roles.*')
            ->get();

        return $roles;
    }

    public function grantedLevelsFor(array $userIds, int $now): array
    {
        if ($userIds === []) {
            return [];
        }

        /** @var array<int, int> $levels */
        $levels = Role::query()
            ->join(UserRole::query()->getModel()->getTable() . ' as ur', 'ur.role_id', '=', 'roles.id')
            ->whereIn('ur.user_id', $userIds)
            ->where(
                static function (Builder $query) use ($now): void {
                    $query->whereNull('ur.expires_at')->orWhere('ur.expires_at', '>', $now);
                }
            )
            ->groupBy('ur.user_id')
            ->selectRaw('ur.user_id AS user_id, MAX(roles.level) AS aggregate')
            ->pluck('aggregate', 'user_id')
            ->all();

        return $levels;
    }

    public function grantedRolesFor(array $userIds, int $now): array
    {
        if ($userIds === []) {
            return [];
        }

        /** @var Collection<int, Role> $rows Static analysis loses the model type through addSelect(). */
        $rows = Role::query()
            ->join(UserRole::query()->getModel()->getTable() . ' as ur', 'ur.role_id', '=', 'roles.id')
            ->whereIn('ur.user_id', $userIds)
            ->where(
                static function (Builder $query) use ($now): void {
                    $query->whereNull('ur.expires_at')->orWhere('ur.expires_at', '>', $now);
                }
            )
            ->select('roles.*')
            ->addSelect('ur.user_id as granted_to')
            ->get();

        $granted = [];

        foreach ($rows as $role) {
            $granted[(int) $role->getAttribute('granted_to')][] = $role;
        }

        return $granted;
    }

    public function permissionCounts(): array
    {
        /** @var array<int, int> $counts */
        $counts = RolePermission::query()
            ->selectRaw('role_id, COUNT(*) AS aggregate')
            ->groupBy('role_id')
            ->pluck('aggregate', 'role_id')
            ->all();

        return $counts;
    }

    public function holderCounts(int $now): array
    {
        /** @var array<int, int> $counts */
        $counts = UserRole::query()
            ->selectRaw('role_id, COUNT(*) AS aggregate')
            ->where(
                static function (Builder $query) use ($now): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                }
            )
            ->groupBy('role_id')
            ->pluck('aggregate', 'role_id')
            ->all();

        return $counts;
    }

    public function create(string $slug, string $name, int $level, int $now): Role
    {
        return Role::query()->create(
            [
                'slug'          => $slug,
                'name'          => $name,
                'level'         => $level,
                // A role the site added has no place on the old numeric scale, so it contributes
                // nothing to the mirrored users.rights.
                'legacy_rights' => null,
                'is_system'     => false,
                'is_default'    => false,
                'is_guest'      => false,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]
        );
    }

    public function update(int $roleId, string $name, int $level, int $now): void
    {
        Role::query()
            ->where('id', '=', $roleId)
            ->update(['name' => $name, 'level' => $level, 'updated_at' => $now]);
    }

    public function delete(int $roleId): void
    {
        RolePermission::query()->where('role_id', '=', $roleId)->delete();
        UserRole::query()->where('role_id', '=', $roleId)->delete();
        Role::query()->where('id', '=', $roleId)->delete();
    }

    public function permissionsFor(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        return RolePermission::query()
            ->whereIn('role_id', $roleIds)
            ->pluck('permission')
            ->unique()
            ->values()
            ->all();
    }

    public function setPermissions(int $roleId, array $permissions): void
    {
        RolePermission::query()->where('role_id', '=', $roleId)->delete();

        $rows = array_map(
            static fn (string $permission): array => ['role_id' => $roleId, 'permission' => $permission],
            array_values(array_unique($permissions))
        );

        if ($rows !== []) {
            RolePermission::query()->insert($rows);
        }
    }

    public function grantsFor(int $userId): array
    {
        /** @var array<int, int|null> $grants */
        $grants = UserRole::query()
            ->where('user_id', '=', $userId)
            ->pluck('expires_at', 'role_id')
            ->all();

        return $grants;
    }

    public function grant(int $userId, int $roleId, ?int $grantedBy, int $grantedAt, ?int $expiresAt = null): void
    {
        UserRole::query()->updateOrCreate(
            ['user_id' => $userId, 'role_id' => $roleId],
            ['granted_by' => $grantedBy, 'granted_at' => $grantedAt, 'expires_at' => $expiresAt]
        );
    }

    public function revoke(int $userId, int $roleId): void
    {
        UserRole::query()->where('user_id', '=', $userId)->where('role_id', '=', $roleId)->delete();
    }
}

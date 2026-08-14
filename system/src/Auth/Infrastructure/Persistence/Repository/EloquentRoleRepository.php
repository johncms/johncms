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

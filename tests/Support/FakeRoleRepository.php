<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleRepositoryInterface;

/**
 * Roles held in memory, so authorization can be tested without a schema.
 *
 * Enough of the writes are implemented to grant and revoke; the ones that shape the catalogue
 * itself belong to a test running against a real table.
 */
final class FakeRoleRepository implements RoleRepositoryInterface
{
    /** @var array<string, Role> */
    private array $roles = [];

    /** @var array<int, list<string>> Permissions per role id. */
    private array $permissions = [];

    /** @var array<int, list<string>> Role slugs granted to a user id. */
    private array $granted = [];

    /** @var array<int, array<int, int|null>> When a grant runs out: user id => role id => time. */
    private array $expiries = [];

    /**
     * @param list<string> $permissions
     */
    public function add(
        string $slug,
        array $permissions = [],
        int $level = 10,
        bool $isDefault = false,
        bool $isGuest = false,
        bool $isSystem = false,
    ): Role {
        $role = new Role();
        $role->forceFill(
            [
                'id'         => count($this->roles) + 1,
                'slug'       => $slug,
                'name'       => $slug,
                'level'      => $level,
                'is_system'  => $isSystem,
                'is_default' => $isDefault,
                'is_guest'   => $isGuest,
            ]
        );

        $this->roles[$slug] = $role;
        $this->permissions[$role->id] = $permissions;

        return $role;
    }

    /**
     * @param list<string> $slugs
     */
    public function grantTo(int $userId, array $slugs): void
    {
        $this->granted[$userId] = $slugs;
    }

    public function all(): Collection
    {
        return new Collection(array_values($this->roles));
    }

    public function findBySlug(string $slug): ?Role
    {
        return $this->roles[$slug] ?? null;
    }

    public function findById(int $id): ?Role
    {
        foreach ($this->roles as $role) {
            if ($role->id === $id) {
                return $role;
            }
        }

        return null;
    }

    public function defaults(): Collection
    {
        return $this->all()->filter(static fn (Role $role): bool => $role->is_default)->values();
    }

    public function guestRole(): ?Role
    {
        return $this->all()->first(static fn (Role $role): bool => $role->is_guest);
    }

    public function forUser(int $userId, int $now): Collection
    {
        return $this->defaults()->merge($this->grantedTo($userId, $now))->unique('id')->values();
    }

    public function grantedTo(int $userId, int $now): Collection
    {
        $slugs = $this->granted[$userId] ?? [];

        return new Collection(array_values(array_filter(
            array_map(fn (string $slug): ?Role => $this->roles[$slug] ?? null, $slugs)
        )));
    }

    public function grantedLevelsFor(array $userIds, int $now): array
    {
        $levels = [];

        foreach ($userIds as $userId) {
            foreach ($this->grantedTo($userId, $now) as $role) {
                $levels[$userId] = max($levels[$userId] ?? 0, $role->level);
            }
        }

        return $levels;
    }

    public function grantedRolesFor(array $userIds, int $now): array
    {
        $granted = [];

        foreach ($userIds as $userId) {
            $roles = $this->grantedTo($userId, $now)->all();

            if ($roles !== []) {
                $granted[$userId] = array_values($roles);
            }
        }

        return $granted;
    }

    public function permissionCounts(): array
    {
        return array_map(static fn (array $permissions): int => count($permissions), $this->permissions);
    }

    public function holderCounts(int $now): array
    {
        $counts = [];

        foreach ($this->granted as $slugs) {
            foreach ($slugs as $slug) {
                $role = $this->findBySlug($slug);

                if ($role !== null) {
                    $counts[$role->id] = ($counts[$role->id] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }

    public function create(string $slug, string $name, int $level, int $now): Role
    {
        $role = $this->add($slug, level: $level);
        $role->name = $name;

        return $role;
    }

    public function update(int $roleId, string $name, int $level, int $now): void
    {
        $role = $this->findById($roleId);

        if ($role !== null) {
            $role->name = $name;
            $role->level = $level;
        }
    }

    public function delete(int $roleId): void
    {
        $role = $this->findById($roleId);

        if ($role === null) {
            return;
        }

        foreach (array_keys($this->granted) as $userId) {
            $this->revoke($userId, $roleId);
        }

        unset($this->roles[$role->slug], $this->permissions[$roleId]);
    }

    public function permissionsFor(array $roleIds): array
    {
        $permissions = [];

        foreach ($roleIds as $id) {
            $permissions = array_merge($permissions, $this->permissions[$id] ?? []);
        }

        return array_values(array_unique($permissions));
    }

    public function setPermissions(int $roleId, array $permissions): void
    {
        $this->permissions[$roleId] = array_values(array_unique($permissions));
    }

    public function grantsFor(int $userId): array
    {
        $grants = [];

        foreach ($this->granted[$userId] ?? [] as $slug) {
            $role = $this->findBySlug($slug);

            if ($role !== null) {
                $grants[$role->id] = $this->expiries[$userId][$role->id] ?? null;
            }
        }

        return $grants;
    }

    public function grant(int $userId, int $roleId, ?int $grantedBy, int $grantedAt, ?int $expiresAt = null): void
    {
        $role = $this->findById($roleId);

        if ($role === null) {
            return;
        }

        if (! in_array($role->slug, $this->granted[$userId] ?? [], true)) {
            $this->granted[$userId][] = $role->slug;
        }

        $this->expiries[$userId][$roleId] = $expiresAt;
    }

    public function revoke(int $userId, int $roleId): void
    {
        $role = $this->findById($roleId);

        if ($role === null) {
            return;
        }

        $this->granted[$userId] = array_values(
            array_filter($this->granted[$userId] ?? [], static fn (string $slug): bool => $slug !== $role->slug)
        );
    }
}

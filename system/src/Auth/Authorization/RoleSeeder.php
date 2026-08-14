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

/**
 * Puts the built-in roles into an installation that does not have them yet.
 *
 * Used by the installer building a fresh site and by the upgrade command bringing an existing
 * one over, so the two cannot end up with different sets.
 *
 * Safe to run again, and deliberately conservative about it: a role that is already there keeps
 * the permissions it has. Re-running must never undo what a site configured — the only thing it
 * fixes is what is missing.
 */
final readonly class RoleSeeder
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    /**
     * @return list<string> The slugs that were created.
     */
    public function seed(?int $now = null): array
    {
        $now ??= time();
        $created = [];

        foreach (SystemRole::cases() as $systemRole) {
            if ($this->roles->findBySlug($systemRole->value) !== null) {
                continue;
            }

            $role = Role::query()->create(
                [
                    'slug'          => $systemRole->value,
                    'name'          => $systemRole->label(),
                    'level'         => $systemRole->level(),
                    'legacy_rights' => $systemRole->legacyRights(),
                    'is_system'     => true,
                    'is_default'    => $systemRole->isDefault(),
                    'is_guest'      => $systemRole->isGuest(),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );

            $this->roles->setPermissions($role->id, $this->initialPermissions($systemRole));
            $created[] = $systemRole->value;
        }

        return $created;
    }

    /**
     * What a freshly created role starts with.
     *
     * Only the admin-panel permissions are granted here, because they are the only ones the core
     * itself declares. What the moderator roles may do in the forum, the library and the rest is
     * granted by those modules as their permissions move over — until then those roles are empty
     * and the numeric mirror is what still answers.
     *
     * The supervisor gets nothing explicitly: SuperAdminVoter answers for that role, which is
     * what keeps a site from being locked out of its own admin panel by a misconfigured matrix.
     *
     * @return list<string>
     */
    private function initialPermissions(SystemRole $role): array
    {
        return match ($role) {
            SystemRole::Admin => [
                CorePermissions::ADMIN_ACCESS,
                CorePermissions::ADMIN_ROLES_MANAGE,
            ],
            default => [],
        };
    }
}

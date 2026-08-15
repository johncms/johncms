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
 * A role is created with the permissions of DefaultPermissions, the same table the command for
 * existing installations applies.
 *
 * Safe to run again, and deliberately conservative about it: a role that is already there keeps
 * the permissions it has. Re-running must never undo what a site configured — the only thing it
 * fixes is what is missing.
 */
final readonly class RoleSeeder
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private DefaultPermissions $defaults,
    ) {
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
                    'slug'       => $systemRole->value,
                    'name'       => $systemRole->label(),
                    'level'      => $systemRole->level(),
                    'is_system'  => true,
                    'is_default' => $systemRole->isDefault(),
                    'is_guest'   => $systemRole->isGuest(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $this->roles->setPermissions($role->id, $this->defaults->forRole($systemRole->value));
            $created[] = $systemRole->value;
        }

        return $created;
    }
}

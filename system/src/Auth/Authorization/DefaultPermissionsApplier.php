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
 * Brings the roles of an existing installation up to the default matrix.
 *
 * Additive on purpose: it grants what a built-in role is missing and never takes anything away.
 * A site that arranged its own matrix keeps every decision it made, and the worst a second run
 * can do is hand back a permission that was revoked — which is why the command around this is
 * guarded and asks for --force.
 *
 * A role the site added itself is not touched at all: the defaults say nothing about it.
 */
final readonly class DefaultPermissionsApplier
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private DefaultPermissions $defaults,
    ) {
    }

    /**
     * @return array<string, list<string>> The keys that were granted, per role slug; roles that
     *                                     needed nothing are absent.
     */
    public function apply(): array
    {
        $granted = [];

        foreach ($this->defaults->all() as $slug => $defaults) {
            $role = $this->roles->findBySlug($slug);

            if ($role === null || $defaults === []) {
                continue;
            }

            $current = $this->roles->permissionsFor([$role->id]);
            $missing = array_values(array_diff($defaults, $current));

            if ($missing === []) {
                continue;
            }

            $this->roles->setPermissions($role->id, [...$current, ...$missing]);
            $granted[$slug] = $missing;
        }

        return $granted;
    }
}

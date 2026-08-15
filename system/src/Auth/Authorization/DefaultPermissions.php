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
 * What each built-in role may do on a site that has not been configured by hand.
 *
 * Assembled from the catalogue rather than written out here: every permission names the roles it
 * belongs to, so a module decides what its own moderator is supposed to have and the core never
 * has to know the module. A module that is switched off declares nothing and its defaults are
 * simply not there.
 *
 * Two callers must not disagree about it: the seeder creating the roles of a fresh installation,
 * and the command bringing an existing one up to the same set.
 *
 * The supervisor is deliberately absent from every list: SuperAdminVoter answers for that level,
 * and permissions granted to it would suggest the list is what makes it powerful.
 */
final readonly class DefaultPermissions
{
    public function __construct(private PermissionRegistry $registry)
    {
    }

    /**
     * Permission keys per role slug.
     *
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        $matrix = [];

        foreach ($this->registry->all() as $definition) {
            foreach ($definition->defaultRoles as $slug) {
                $matrix[$slug][] = $definition->key;
            }
        }

        return $matrix;
    }

    /**
     * @return list<string>
     */
    public function forRole(string $slug): array
    {
        return $this->all()[$slug] ?? [];
    }
}

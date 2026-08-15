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
 * One table, for two callers that must not disagree: the seeder creating the roles of a fresh
 * installation, and the command bringing an existing installation up to the same set. It is
 * written as the numeric checks are converted — a permission arrives here in the same commit that
 * starts asking for it, so an upgraded site keeps doing what it did before.
 *
 * The supervisor is deliberately absent: SuperAdminVoter answers for that level, and listing
 * permissions for it would suggest the list is what makes it powerful.
 */
final class DefaultPermissions
{
    /**
     * Permission keys per role slug.
     *
     * @return array<string, list<string>>
     */
    public static function all(): array
    {
        return [
            SystemRole::Guest->value              => [],
            SystemRole::User->value               => [],
            SystemRole::ForumModerator->value     => [
                CorePermissions::ANTIFLOOD_RELAXED,
            ],
            SystemRole::DownloadsModerator->value => [
                CorePermissions::ANTIFLOOD_RELAXED,
            ],
            SystemRole::LibraryModerator->value   => [
                CorePermissions::ANTIFLOOD_RELAXED,
            ],
            SystemRole::SuperModerator->value     => [
                CorePermissions::ANTIFLOOD_RELAXED,
                CorePermissions::USERS_ORIGIN_VIEW,
            ],
            SystemRole::Admin->value              => [
                CorePermissions::ADMIN_ACCESS,
                CorePermissions::ADMIN_ROLES_MANAGE,
                CorePermissions::ANTIFLOOD_RELAXED,
                CorePermissions::USERS_ORIGIN_VIEW,
                CorePermissions::SYSTEM_DEBUG_VIEW,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function forRole(string $slug): array
    {
        return self::all()[$slug] ?? [];
    }
}

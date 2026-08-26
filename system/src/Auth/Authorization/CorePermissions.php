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
 * The permissions the core itself checks. Modules declare their own the same way, by
 * registering a provider with the `johncms.auth.permissions` tag.
 */
final class CorePermissions implements PermissionProviderInterface
{
    public const GROUP = 'admin';

    public const SYSTEM_GROUP = 'system';

    public const USERS_GROUP = 'users';

    /** Enter the admin panel at all. */
    public const ADMIN_ACCESS = 'admin.access';

    /** Change what affects the whole site: system settings, IP bans, module structure. */
    public const ADMIN_SETTINGS_MANAGE = 'admin.settings.manage';

    /** Manage roles and what each of them may do. */
    public const ADMIN_ROLES_MANAGE = 'admin.roles.manage';

    /** Read the details of a failure and the figures of the developer panel. */
    public const SYSTEM_DEBUG_VIEW = 'system.debug.view';

    /** Post again after the short fixed delay instead of the one the site configured. */
    public const ANTIFLOOD_RELAXED = 'system.antiflood.relaxed';

    /** See where a visitor came from and where on the site they are. */
    public const USERS_ORIGIN_VIEW = 'users.origin.view';

    /** Browse the site as another account, to see what they see. */
    public const USERS_IMPERSONATE = 'users.impersonate';

    /** Use the smilies kept for the staff. */
    public const SMILIES_ADMIN_USE = 'system.smilies.admin';

    /** Reply to, edit and delete the comments the modules keep in the shared engine. */
    public const COMMENTS_MODERATE = 'system.comments.moderate';

    /**
     * Install, update, switch off and remove modules.
     *
     * Installing a module is running its code on this site, so no built-in role carries this:
     * the supervisor is allowed everything by SuperAdminVoter, and anybody else has to be given
     * it deliberately.
     */
    public const MODULES_MANAGE = 'system.modules.manage';

    public function permissions(): iterable
    {
        $adminGroup = d__('system', 'Admin panel');
        $systemGroup = d__('system', 'System');
        $usersGroup = d__('system', 'Users');

        $staff = [
            SystemRole::ForumModerator->value,
            SystemRole::DownloadsModerator->value,
            SystemRole::LibraryModerator->value,
            SystemRole::SuperModerator->value,
            SystemRole::Admin->value,
        ];
        $administrators = [SystemRole::Admin->value];

        return [
            new PermissionDefinition(
                self::ADMIN_ACCESS,
                self::GROUP,
                d__('system', 'Access the admin panel'),
                $adminGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::ADMIN_SETTINGS_MANAGE,
                self::GROUP,
                d__('system', 'Change system settings'),
                $adminGroup
            ),
            new PermissionDefinition(
                self::ADMIN_ROLES_MANAGE,
                self::GROUP,
                d__('system', 'Manage roles and permissions'),
                $adminGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::SYSTEM_DEBUG_VIEW,
                self::SYSTEM_GROUP,
                d__('system', 'See error details and the developer panel'),
                $systemGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::ANTIFLOOD_RELAXED,
                self::SYSTEM_GROUP,
                d__('system', 'Post without waiting out the full antiflood delay'),
                $systemGroup,
                $staff
            ),
            new PermissionDefinition(
                self::SMILIES_ADMIN_USE,
                self::SYSTEM_GROUP,
                d__('system', 'Use the smilies kept for the staff'),
                $systemGroup,
                $staff
            ),
            new PermissionDefinition(
                self::COMMENTS_MODERATE,
                self::SYSTEM_GROUP,
                d__('system', 'Moderate the comments of the modules'),
                $systemGroup,
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
            // No default roles: whoever holds this can put arbitrary code on the site.
            new PermissionDefinition(
                self::MODULES_MANAGE,
                self::SYSTEM_GROUP,
                d__('system', 'Install and remove modules'),
                $systemGroup
            ),
            new PermissionDefinition(
                self::USERS_ORIGIN_VIEW,
                self::USERS_GROUP,
                d__('system', 'See where a visitor is on the site and where they came from'),
                $usersGroup,
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
            new PermissionDefinition(
                self::USERS_IMPERSONATE,
                self::USERS_GROUP,
                d__('system', 'Browse the site as another user'),
                $usersGroup,
                $administrators
            ),
        ];
    }
}

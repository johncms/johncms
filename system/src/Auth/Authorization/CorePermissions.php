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

    /** Open an account that has not been confirmed yet. */
    public const USERS_UNCONFIRMED_VIEW = 'users.unconfirmed.view';

    /** Edit somebody else's profile. */
    public const USERS_PROFILE_EDIT = 'users.profile.edit';

    /** Change somebody else's password. */
    public const USERS_PASSWORD_CHANGE = 'users.password.change';

    /** Reset the settings of somebody else's account. */
    public const USERS_SETTINGS_RESET = 'users.settings.reset';

    /** The notes the staff keep about an account. */
    public const USERS_NOTES_VIEW = 'users.notes.view';

    /** Read the address of an account that keeps it hidden. */
    public const USERS_EMAIL_VIEW = 'users.email.view';

    /** Ban an account from the site, private messages, comments and the guestbook. */
    public const USERS_BAN_MANAGE = 'users.ban.manage';

    /** Ban an account from the forum. */
    public const USERS_BAN_FORUM = 'users.ban.forum';

    /** Ban an account from the library. */
    public const USERS_BAN_LIBRARY = 'users.ban.library';

    /** Ban without an end date, and lift a ban before it runs out. */
    public const USERS_BAN_PERMANENT = 'users.ban.permanent';

    /** Delete the record of a ban, not just end it. */
    public const USERS_BAN_DESTROY = 'users.ban.destroy';

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
        $seniorStaff = [SystemRole::SuperModerator->value, SystemRole::Admin->value];

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
            new PermissionDefinition(
                self::USERS_UNCONFIRMED_VIEW,
                self::USERS_GROUP,
                d__('system', 'Open an account awaiting confirmation'),
                $usersGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::USERS_PROFILE_EDIT,
                self::USERS_GROUP,
                d__('system', 'Edit the profile of another account'),
                $usersGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::USERS_PASSWORD_CHANGE,
                self::USERS_GROUP,
                d__('system', 'Change the password of another account'),
                $usersGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::USERS_SETTINGS_RESET,
                self::USERS_GROUP,
                d__('system', 'Reset the settings of another account'),
                $usersGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::USERS_NOTES_VIEW,
                self::USERS_GROUP,
                d__('system', 'Read the notes the staff keep about an account'),
                $usersGroup,
                $staff
            ),
            new PermissionDefinition(
                self::USERS_EMAIL_VIEW,
                self::USERS_GROUP,
                d__('system', 'Read the e-mail address of an account that hides it'),
                $usersGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::USERS_BAN_MANAGE,
                self::USERS_GROUP,
                d__('system', 'Ban from the site, private messages, comments and the guestbook'),
                $usersGroup,
                $seniorStaff
            ),
            new PermissionDefinition(
                self::USERS_BAN_FORUM,
                self::USERS_GROUP,
                d__('system', 'Ban from the forum'),
                $usersGroup,
                [SystemRole::ForumModerator->value, ...$seniorStaff]
            ),
            new PermissionDefinition(
                self::USERS_BAN_LIBRARY,
                self::USERS_GROUP,
                d__('system', 'Ban from the library'),
                $usersGroup,
                [SystemRole::LibraryModerator->value, ...$seniorStaff]
            ),
            new PermissionDefinition(
                self::USERS_BAN_PERMANENT,
                self::USERS_GROUP,
                d__('system', 'Ban without an end date and lift a ban'),
                $usersGroup,
                $administrators
            ),
            new PermissionDefinition(
                self::USERS_BAN_DESTROY,
                self::USERS_GROUP,
                d__('system', 'Delete the record of a ban'),
                $usersGroup
            ),
        ];
    }
}

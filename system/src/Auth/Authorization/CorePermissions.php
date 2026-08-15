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

    /** Enter the admin panel at all. */
    public const ADMIN_ACCESS = 'admin.access';

    /** Change what affects the whole site: system settings, IP bans, module structure. */
    public const ADMIN_SETTINGS_MANAGE = 'admin.settings.manage';

    /** Manage roles and what each of them may do. */
    public const ADMIN_ROLES_MANAGE = 'admin.roles.manage';

    public function permissions(): iterable
    {
        $group = d__('system', 'Admin panel');

        return [
            new PermissionDefinition(
                self::ADMIN_ACCESS,
                self::GROUP,
                d__('system', 'Access the admin panel'),
                $group
            ),
            new PermissionDefinition(
                self::ADMIN_SETTINGS_MANAGE,
                self::GROUP,
                d__('system', 'Change system settings'),
                $group
            ),
            new PermissionDefinition(
                self::ADMIN_ROLES_MANAGE,
                self::GROUP,
                d__('system', 'Manage roles and permissions'),
                $group
            ),
        ];
    }
}

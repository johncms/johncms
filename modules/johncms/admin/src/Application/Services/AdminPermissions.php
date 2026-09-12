<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * The permissions of the admin panel that are finer than "may open the panel".
 *
 * They stand for the checks that used to sit inside a screen — the ones that asked for the
 * highest access level even though the screen itself was open to any administrator.
 */
final class AdminPermissions implements PermissionProviderInterface
{
    public const GROUP = 'admin';

    /** Open the report on the state of the installation: versions, extensions, permissions. */
    public const SYSTEM_CHECK = 'admin.system_check';

    /** Read the sign-in log: who signed in, what was refused, who changed whose roles. */
    public const AUTH_LOG_VIEW = 'admin.auth_log.view';

    public function permissions(): iterable
    {
        $group = d__('admin', 'Admin Panel');

        return [
            new PermissionDefinition(
                self::SYSTEM_CHECK,
                self::GROUP,
                d__('admin', 'Open the report on the state of the installation'),
                $group,
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
            new PermissionDefinition(
                self::AUTH_LOG_VIEW,
                self::GROUP,
                d__('admin', 'Read the sign-in log'),
                $group,
                [SystemRole::Admin->value]
            ),
        ];
    }
}

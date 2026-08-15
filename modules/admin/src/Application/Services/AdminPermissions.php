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

    /** Delete a forum section together with the topics and the posts inside it. */
    public const FORUM_STRUCTURE_DESTROY = 'admin.forum.structure.destroy';

    /** Empty the lists of hidden topics and posts for good. */
    public const FORUM_HIDDEN_PURGE = 'admin.forum.hidden.purge';

    /** Open the report on the state of the installation: versions, extensions, permissions. */
    public const SYSTEM_CHECK = 'admin.system_check';

    public function permissions(): iterable
    {
        $group = d__('admin', 'Admin Panel');

        return [
            new PermissionDefinition(
                self::FORUM_STRUCTURE_DESTROY,
                self::GROUP,
                d__('admin', 'Delete a forum section with everything in it'),
                $group
            ),
            new PermissionDefinition(
                self::FORUM_HIDDEN_PURGE,
                self::GROUP,
                d__('admin', 'Empty the lists of hidden topics and posts'),
                $group
            ),
            new PermissionDefinition(
                self::SYSTEM_CHECK,
                self::GROUP,
                d__('admin', 'Open the report on the state of the installation'),
                $group,
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
        ];
    }
}

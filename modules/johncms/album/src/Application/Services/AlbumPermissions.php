<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * What may be done to an album somebody else owns.
 */
final class AlbumPermissions implements PermissionProviderInterface
{
    public const GROUP = 'album';

    /**
     * Open, edit, sort and delete the albums and the photos of another account, private ones
     * and password-protected ones included.
     */
    public const MODERATE = 'album.moderate';

    /** Read the comments of the photos while they are switched off for everybody else. */
    public const COMMENTS_ALWAYS_VIEW = 'album.comments.always_view';

    public function permissions(): iterable
    {
        $group = d__('album', 'Photo albums');

        return [
            new PermissionDefinition(
                self::MODERATE,
                self::GROUP,
                d__('album', 'Manage the albums and the photos of another account'),
                $group,
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
            new PermissionDefinition(
                self::COMMENTS_ALWAYS_VIEW,
                self::GROUP,
                d__('album', 'Read the comments of the photos while they are switched off'),
                $group,
                [SystemRole::Admin->value]
            ),
        ];
    }
}

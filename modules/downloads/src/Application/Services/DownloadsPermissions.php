<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * What may be done in the downloads beyond taking a file.
 *
 * Three numbers stood for all of it — 4 or 6 and up for the staff of the section, 7 and up for
 * whoever was allowed past a switched-off setting, 9 for the one who decides what users may
 * upload. Each of them is a key here.
 */
final class DownloadsPermissions implements PermissionProviderInterface
{
    public const GROUP = 'downloads';

    /** Create and change folders, edit files, and open the ones awaiting moderation. */
    public const MODERATE = 'downloads.moderate';

    /** Move a file to another folder. */
    public const FILE_MOVE = 'downloads.file.move';

    /** Decide whether users may upload into a folder, and in which formats. */
    public const UPLOAD_RULES_MANAGE = 'downloads.upload_rules.manage';

    /** Read the comments of the files while they are switched off for everybody else. */
    public const COMMENTS_ALWAYS_VIEW = 'downloads.comments.always_view';

    public function permissions(): iterable
    {
        $group = d__('downloads', 'Downloads');
        $staff = [
            SystemRole::DownloadsModerator->value,
            SystemRole::SuperModerator->value,
            SystemRole::Admin->value,
        ];
        $administrators = [SystemRole::Admin->value];

        return [
            new PermissionDefinition(
                self::MODERATE,
                self::GROUP,
                d__('downloads', 'Manage folders and files'),
                $group,
                $staff
            ),
            new PermissionDefinition(
                self::FILE_MOVE,
                self::GROUP,
                d__('downloads', 'Move a file to another folder'),
                $group,
                $administrators
            ),
            new PermissionDefinition(
                self::UPLOAD_RULES_MANAGE,
                self::GROUP,
                d__('downloads', 'Decide who may upload into a folder and in which formats'),
                $group
            ),
            new PermissionDefinition(
                self::COMMENTS_ALWAYS_VIEW,
                self::GROUP,
                d__('downloads', 'Read the comments of the files while they are switched off'),
                $group,
                $administrators
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * What the staff may do in the guestbook.
 */
final class GuestbookPermissions implements PermissionProviderInterface
{
    public const GROUP = 'guestbook';

    /** Edit and delete the entries of other people. */
    public const ENTRY_MANAGE = 'guestbook.entry.manage';

    /** Answer an entry with the reply that is shown as coming from the staff. */
    public const ENTRY_REPLY = 'guestbook.entry.reply';

    /** Empty the guestbook. */
    public const CLEAR = 'guestbook.clear';

    /** Enter the admin club — the second guestbook, kept apart from the public one. */
    public const ADMIN_CLUB_VIEW = 'guestbook.admin_club.view';

    public function permissions(): iterable
    {
        $group = d__('guestbook', 'Guestbook');
        $staff = [
            SystemRole::ForumModerator->value,
            SystemRole::DownloadsModerator->value,
            SystemRole::LibraryModerator->value,
            SystemRole::SuperModerator->value,
            SystemRole::Admin->value,
        ];

        return [
            new PermissionDefinition(
                self::ENTRY_MANAGE,
                self::GROUP,
                d__('guestbook', 'Edit and delete the entries of other people'),
                $group,
                $staff
            ),
            new PermissionDefinition(
                self::ENTRY_REPLY,
                self::GROUP,
                d__('guestbook', 'Answer an entry on behalf of the staff'),
                $group,
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
            new PermissionDefinition(
                self::CLEAR,
                self::GROUP,
                d__('guestbook', 'Empty the guestbook'),
                $group,
                [SystemRole::Admin->value]
            ),
            new PermissionDefinition(
                self::ADMIN_CLUB_VIEW,
                self::GROUP,
                d__('guestbook', 'Enter the admin club'),
                $group,
                $staff
            ),
        ];
    }
}

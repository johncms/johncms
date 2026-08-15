<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * What may be done to somebody else's account.
 *
 * The old code asked the same question everywhere — "is my number bigger than theirs" — and the
 * answer stood for a dozen different things: opening a profile awaiting confirmation, editing it,
 * changing its password, banning it, deleting the record of a ban. Each of those is a key here,
 * and "bigger than theirs" is now the level of the roles, checked next to the permission.
 */
final class ProfilePermissions implements PermissionProviderInterface
{
    /** The core declares the title of this group; the keys of the profile join it. */
    public const GROUP = 'users';

    /** Open an account that has not been confirmed yet. */
    public const UNCONFIRMED_VIEW = 'users.unconfirmed.view';

    /** Edit somebody else's profile. */
    public const PROFILE_EDIT = 'users.profile.edit';

    /** Change somebody else's password. */
    public const PASSWORD_CHANGE = 'users.password.change';

    /** Reset the settings of somebody else's account. */
    public const SETTINGS_RESET = 'users.settings.reset';

    /** The notes the staff keep about an account. */
    public const NOTES_VIEW = 'users.notes.view';

    /** Read the address of an account that keeps it hidden. */
    public const EMAIL_VIEW = 'users.email.view';

    /** Ban an account from the site, private messages, comments and the guestbook. */
    public const BAN_MANAGE = 'users.ban.manage';

    /** Ban an account from the forum. */
    public const BAN_FORUM = 'users.ban.forum';

    /** Ban an account from the library. */
    public const BAN_LIBRARY = 'users.ban.library';

    /** Ban without an end date, and lift a ban before it runs out. */
    public const BAN_PERMANENT = 'users.ban.permanent';

    /** Delete the record of a ban, not just end it. */
    public const BAN_DESTROY = 'users.ban.destroy';

    /** Delete a karma entry. */
    public const KARMA_DESTROY = 'users.karma.destroy';

    public function permissions(): iterable
    {
        $administrators = [SystemRole::Admin->value];
        $seniorStaff = [SystemRole::SuperModerator->value, SystemRole::Admin->value];
        $staff = [
            SystemRole::ForumModerator->value,
            SystemRole::DownloadsModerator->value,
            SystemRole::LibraryModerator->value,
            SystemRole::SuperModerator->value,
            SystemRole::Admin->value,
        ];

        return [
            new PermissionDefinition(
                self::UNCONFIRMED_VIEW,
                self::GROUP,
                d__('profile', 'Open an account awaiting confirmation'),
                null,
                $administrators
            ),
            new PermissionDefinition(
                self::PROFILE_EDIT,
                self::GROUP,
                d__('profile', 'Edit the profile of another account'),
                null,
                $administrators
            ),
            new PermissionDefinition(
                self::PASSWORD_CHANGE,
                self::GROUP,
                d__('profile', 'Change the password of another account'),
                null,
                $administrators
            ),
            new PermissionDefinition(
                self::SETTINGS_RESET,
                self::GROUP,
                d__('profile', 'Reset the settings of another account'),
                null,
                $administrators
            ),
            new PermissionDefinition(
                self::NOTES_VIEW,
                self::GROUP,
                d__('profile', 'Read the notes the staff keep about an account'),
                null,
                $staff
            ),
            new PermissionDefinition(
                self::EMAIL_VIEW,
                self::GROUP,
                d__('profile', 'Read the e-mail address of an account that hides it'),
                null,
                $administrators
            ),
            new PermissionDefinition(
                self::BAN_MANAGE,
                self::GROUP,
                d__('profile', 'Ban from the site, private messages, comments and the guestbook'),
                null,
                $seniorStaff
            ),
            new PermissionDefinition(
                self::BAN_FORUM,
                self::GROUP,
                d__('profile', 'Ban from the forum'),
                null,
                [SystemRole::ForumModerator->value, ...$seniorStaff]
            ),
            new PermissionDefinition(
                self::BAN_LIBRARY,
                self::GROUP,
                d__('profile', 'Ban from the library'),
                null,
                [SystemRole::LibraryModerator->value, ...$seniorStaff]
            ),
            new PermissionDefinition(
                self::BAN_PERMANENT,
                self::GROUP,
                d__('profile', 'Ban without an end date and lift a ban'),
                null,
                $administrators
            ),
            new PermissionDefinition(
                self::BAN_DESTROY,
                self::GROUP,
                d__('profile', 'Delete the record of a ban')
            ),
            new PermissionDefinition(
                self::KARMA_DESTROY,
                self::GROUP,
                d__('profile', 'Delete a karma entry')
            ),
        ];
    }
}

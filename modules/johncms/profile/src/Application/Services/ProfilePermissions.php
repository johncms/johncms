<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;

/**
 * What the profile adds to the permissions of the users. What may be done to somebody else's
 * account — editing it, banning it — is declared by the core (CorePermissions::USERS_*): the
 * panel does it as well, and it has to be possible with this module switched off.
 */
final class ProfilePermissions implements PermissionProviderInterface
{
    /** The core declares the title of this group; the keys of the profile join it. */
    public const GROUP = 'users';

    /** Delete a karma entry. */
    public const KARMA_DESTROY = 'users.karma.destroy';

    public function permissions(): iterable
    {
        return [
            new PermissionDefinition(
                self::KARMA_DESTROY,
                self::GROUP,
                d__('profile', 'Delete a karma entry')
            ),
        ];
    }
}

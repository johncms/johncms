<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;

/**
 * The collections exist only in the admin panel so far, behind a single permission.
 */
final class CollectionsPermissions implements PermissionProviderInterface
{
    public const GROUP = 'collections';

    /** Create and change the collections, their fields and their items. */
    public const MANAGE = 'collections.manage';

    public function permissions(): iterable
    {
        // Nobody by default: the screens were the supervisor's alone, and the supervisor is
        // answered by their level rather than by a list of permissions.
        return [
            new PermissionDefinition(
                self::MANAGE,
                self::GROUP,
                d__('collections', 'Manage the collections'),
                d__('collections', 'Collections')
            ),
        ];
    }
}

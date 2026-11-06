<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;

/**
 * Schema administration; collection-specific content permissions are configured separately.
 */
final class CollectionsPermissions implements PermissionProviderInterface
{
    public const GROUP = 'collections';

    /** Create and change the collections, their fields and their items. */
    public const MANAGE = 'collections.manage';

    public const CREATE = 'collections.items.create';
    public const EDIT = 'collections.items.edit';
    public const PUBLISH = 'collections.items.publish';
    public const DELETE = 'collections.items.delete';
    public const SECTIONS = 'collections.sections.manage';

    public function permissions(): iterable
    {
        // Nobody by default: the screens were the supervisor's alone, and the supervisor is
        // answered by their level rather than by a list of permissions.
        $extra = [];
        foreach (
            [
            self::CREATE => d__('collections', 'Create items'), self::EDIT => d__('collections', 'Edit items'),
            self::PUBLISH => d__('collections', 'Publish items'), self::DELETE => d__('collections', 'Delete items'),
            self::SECTIONS => d__('collections', 'Manage sections'),
            ] as $key => $label
        ) {
            $extra[] = new PermissionDefinition($key, self::GROUP, $label, d__('collections', 'Collections'));
        }
        return [
            ...$extra,
            new PermissionDefinition(
                self::MANAGE,
                self::GROUP,
                d__('collections', 'Manage the collections'),
                d__('collections', 'Collections')
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * What may be done in the library beyond reading it.
 */
final class LibraryPermissions implements PermissionProviderInterface
{
    public const GROUP = 'library';

    /**
     * Create and change the sections and the articles, and read the ones still waiting to be
     * published.
     */
    public const MODERATE = 'library.moderate';

    public function permissions(): iterable
    {
        return [
            new PermissionDefinition(
                self::MODERATE,
                self::GROUP,
                d__('library', 'Manage the sections and the articles'),
                d__('library', 'Library'),
                [
                    SystemRole::LibraryModerator->value,
                    SystemRole::SuperModerator->value,
                    SystemRole::Admin->value,
                ]
            ),
        ];
    }
}

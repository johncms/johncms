<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * The lists of the community: who is registered, who has a birthday, who is at the top.
 */
final class CommunityPermissions implements PermissionProviderInterface
{
    public const GROUP = 'community';

    /** Open the lists of accounts. What the "community" setting used to say about guests. */
    public const VIEW = 'community.view';

    public function permissions(): iterable
    {
        return [
            new PermissionDefinition(
                self::VIEW,
                self::GROUP,
                d__('community', 'Open the lists of the community'),
                d__('community', 'Community'),
                [SystemRole::Guest->value, SystemRole::User->value]
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * What the staff may do to the comments of the news.
 */
final class NewsPermissions implements PermissionProviderInterface
{
    public const GROUP = 'news';

    /** Delete the comment of anybody, and see where it was written from. */
    public const COMMENTS_MODERATE = 'news.comments.moderate';

    public function permissions(): iterable
    {
        return [
            new PermissionDefinition(
                self::COMMENTS_MODERATE,
                self::GROUP,
                d__('news', 'Delete the comments of other people'),
                d__('news', 'News'),
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
        ];
    }
}

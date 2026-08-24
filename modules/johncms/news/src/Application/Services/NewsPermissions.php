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

    /** Write a comment under an article, and attach an image to it. */
    public const COMMENTS_POST = 'news.comments.post';

    /** The news in the admin panel: the sections, the articles and the settings of the module. */
    public const MANAGE = 'news.manage';

    public function permissions(): iterable
    {
        $group = d__('news', 'News');

        return [
            new PermissionDefinition(
                self::COMMENTS_MODERATE,
                self::GROUP,
                d__('news', 'Delete the comments of other people'),
                $group,
                [SystemRole::SuperModerator->value, SystemRole::Admin->value]
            ),
            new PermissionDefinition(
                self::COMMENTS_POST,
                self::GROUP,
                d__('news', 'Comment on the news'),
                $group,
                [SystemRole::User->value]
            ),
            // Nobody by default: the news were administered by the supervisor alone, and the
            // supervisor is answered by their level rather than by a list.
            new PermissionDefinition(
                self::MANAGE,
                self::GROUP,
                d__('news', 'Manage the news'),
                $group
            ),
        ];
    }
}

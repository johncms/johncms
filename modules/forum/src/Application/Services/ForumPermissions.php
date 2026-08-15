<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * What the forum asks about instead of the numeric access level.
 *
 * The old number could only say how much authority somebody had: 3 was a forum moderator, 9 the
 * one who may destroy things for good, and everything in between was a comparison nobody could
 * read. Each of those comparisons is a key here.
 */
final class ForumPermissions implements PermissionProviderInterface
{
    public const GROUP = 'forum';

    /** Close, pin, move, edit and restore topics; edit and delete the posts of others. */
    public const TOPIC_MODERATE = 'forum.topic.moderate';

    /** Add, edit and remove the poll of a topic. */
    public const POLL_MANAGE = 'forum.poll.manage';

    /** See topics and posts that were deleted. */
    public const DELETED_VIEW = 'forum.deleted.view';

    /** Appoint the curators of a topic. */
    public const CURATORS_MANAGE = 'forum.curators.manage';

    /** See who voted for what in a poll. */
    public const POLL_VOTERS_VIEW = 'forum.poll.voters.view';

    /** Delete a topic for good, together with everything in it. */
    public const TOPIC_DESTROY = 'forum.topic.destroy';

    /** Delete a post for good, rather than marking it deleted. */
    public const POST_DESTROY = 'forum.post.destroy';

    /** Fill in the keywords and the description of a topic. */
    public const TOPIC_META_MANAGE = 'forum.topic.meta.manage';

    public function permissions(): iterable
    {
        $group = d__('forum', 'Forum');

        // Who moderates the forum out of the box. The downloads and the library moderators are
        // not among them: the single number used to make "moderator" one rank, and roles are the
        // point at which those stop being the same job.
        $moderators = [
            SystemRole::ForumModerator->value,
            SystemRole::SuperModerator->value,
            SystemRole::Admin->value,
        ];
        $administrators = [SystemRole::Admin->value];

        return [
            new PermissionDefinition(
                self::TOPIC_MODERATE,
                self::GROUP,
                d__('forum', 'Moderate topics: close, pin, move, edit, restore'),
                $group,
                $moderators
            ),
            new PermissionDefinition(
                self::POLL_MANAGE,
                self::GROUP,
                d__('forum', 'Manage the poll of a topic'),
                $group,
                $moderators
            ),
            new PermissionDefinition(
                self::DELETED_VIEW,
                self::GROUP,
                d__('forum', 'See deleted topics and posts'),
                $group,
                $administrators
            ),
            new PermissionDefinition(
                self::CURATORS_MANAGE,
                self::GROUP,
                d__('forum', 'Appoint the curators of a topic'),
                $group,
                $administrators
            ),
            new PermissionDefinition(
                self::POLL_VOTERS_VIEW,
                self::GROUP,
                d__('forum', 'See who voted in a poll'),
                $group,
                $administrators
            ),
            new PermissionDefinition(
                self::TOPIC_DESTROY,
                self::GROUP,
                d__('forum', 'Delete a topic for good'),
                $group
            ),
            new PermissionDefinition(
                self::POST_DESTROY,
                self::GROUP,
                d__('forum', 'Delete a post for good'),
                $group
            ),
            new PermissionDefinition(
                self::TOPIC_META_MANAGE,
                self::GROUP,
                d__('forum', 'Fill in the keywords and the description of a topic'),
                $group,
                $moderators
            ),
        ];
    }
}

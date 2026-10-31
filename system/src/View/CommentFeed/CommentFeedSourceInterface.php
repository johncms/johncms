<?php

declare(strict_types=1);

namespace Johncms\View\CommentFeed;

/**
 * How a module puts the comments its visitors leave — on files, articles, photos — into the one
 * feed of the panel, so the staff read them in a single place instead of going through every
 * module.
 *
 * A module implements this, the container tags it, and the feed merges what the switched-on
 * modules return, the newest first. A source answers only the visitor who moderates its comments
 * and is empty for everybody else: a comment under a private photo is no business of the panel as
 * such.
 */
interface CommentFeedSourceInterface
{
    /**
     * @return list<FeedComment> The newest comments, at most $limit, the newest first.
     */
    public function latestComments(int $limit): array;

    public function countComments(): int;
}

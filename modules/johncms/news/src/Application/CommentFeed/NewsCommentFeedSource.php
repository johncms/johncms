<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\CommentFeed;

use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;

final readonly class NewsCommentFeedSource implements CommentFeedSourceInterface
{
    public function __construct(private NewsDiscussionServiceInterface $discussion, private NewsCollectionAvailability $availability)
    {
    }

    public function latestComments(int $limit): array
    {
        return $this->availability->ready() ? $this->discussion->latestFeed($limit) : [];
    }

    public function countComments(): int
    {
        return $this->availability->ready() ? $this->discussion->countFeed() : 0;
    }
}

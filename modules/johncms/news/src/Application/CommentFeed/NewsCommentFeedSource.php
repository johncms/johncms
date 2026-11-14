<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\CommentFeed;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Comments\Application\CommentsService;
use Johncms\Comments\Application\DTO\LatestCommentDTO;
use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Comments\NewsCommentSubject;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;
use Johncms\View\CommentFeed\FeedComment;

/**
 * The comments on the news, for their moderator.
 */
final readonly class NewsCommentFeedSource implements CommentFeedSourceInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CommentsService $comments,
        private NewsCollectionAvailability $availability,
    ) {
    }

    public function latestComments(int $limit): array
    {
        if (! $this->moderates()) {
            return [];
        }

        return array_map($this->toFeed(...), $this->comments->latest(NewsCommentSubject::TYPE, $limit, 0));
    }

    public function countComments(): int
    {
        return $this->moderates() ? $this->comments->countOfType(NewsCommentSubject::TYPE) : 0;
    }

    private function moderates(): bool
    {
        return $this->availability->ready() && $this->accessChecker->allows(NewsPermissions::COMMENTS_MODERATE);
    }

    private function toFeed(LatestCommentDTO $latest): FeedComment
    {
        return new FeedComment(
            section: d__('news', 'News'),
            subject: $latest->subject->title,
            url: $latest->permalink(),
            authorId: $latest->authorId,
            authorName: $latest->comment->author->name ?? $latest->comment->authorName,
            text: $latest->html,
            time: $latest->writtenAt,
        );
    }
}

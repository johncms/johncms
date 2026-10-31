<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\CommentFeed;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\Modules\News\Domain\Models\NewsComments;
use Johncms\Modules\News\Domain\Repository\NewsCommentRepositoryInterface;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;
use Johncms\View\CommentFeed\FeedComment;

/**
 * The comments on the news, for whoever moderates them.
 */
final readonly class NewsCommentFeedSource implements CommentFeedSourceInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NewsCommentRepositoryInterface $comments,
    ) {
    }

    public function latestComments(int $limit): array
    {
        if (! $this->moderates()) {
            return [];
        }

        return array_values($this->comments->getLatest($limit)->map($this->toFeed(...))->all());
    }

    public function countComments(): int
    {
        return $this->moderates() ? $this->comments->countAll() : 0;
    }

    private function moderates(): bool
    {
        return $this->accessChecker->allows(NewsPermissions::COMMENTS_MODERATE);
    }

    private function toFeed(NewsComments $comment): FeedComment
    {
        return new FeedComment(
            d__('news', 'News'),
            (string) $comment->article?->name,
            // The comments are drawn on the page of the article; their own address answers the script.
            $comment->article === null ? '/news/' : $comment->article->url . '#article-comments',
            (int) $comment->getAttribute('user_id'),
            (string) $comment->user?->name,
            (string) $comment->getAttribute('text'),
            // The cast of the model turns the date into words; the feed sorts by the moment itself.
            (int) strtotime((string) $comment->getRawOriginal('created_at')),
        );
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\CommentFeed;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Domain\Models\LibraryComment;
use Johncms\Modules\Library\Domain\Repository\LibraryCommentRepositoryInterface;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;
use Johncms\View\CommentFeed\FeedComment;

/**
 * The comments on the articles, for the moderator of the library.
 */
final readonly class LibraryCommentFeedSource implements CommentFeedSourceInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private LibraryCommentRepositoryInterface $comments,
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
        return $this->accessChecker->allows(LibraryPermissions::MODERATE);
    }

    private function toFeed(LibraryComment $comment): FeedComment
    {
        // The name the author had when writing, kept with the comment by the legacy comments.
        $attributes = unserialize((string) $comment->getAttribute('attributes'), ['allowed_classes' => false]);

        return new FeedComment(
            d__('library', 'Library'),
            (string) $comment->getAttribute('article_name'),
            '/library/article/' . $comment->sub_id . '/comments',
            $comment->user_id,
            (string) ($attributes['author_name'] ?? ''),
            $comment->text,
            $comment->time,
        );
    }
}

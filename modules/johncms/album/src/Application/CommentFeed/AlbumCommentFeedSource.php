<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\CommentFeed;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Models\AlbumComment;
use Johncms\Modules\Album\Domain\Repository\AlbumCommentRepositoryInterface;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;
use Johncms\View\CommentFeed\FeedComment;

/**
 * The comments on the photos, for the moderator of the albums — the one who may open a private
 * album, and so read what was written under its photos.
 */
final readonly class AlbumCommentFeedSource implements CommentFeedSourceInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumCommentRepositoryInterface $comments,
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
        return $this->accessChecker->allows(AlbumPermissions::MODERATE);
    }

    private function toFeed(AlbumComment $comment): FeedComment
    {
        // The name the author had when writing, kept with the comment by the legacy comments.
        $attributes = unserialize((string) $comment->getAttribute('attributes'), ['allowed_classes' => false]);

        return new FeedComment(
            d__('album', 'Photo albums'),
            (string) $comment->getAttribute('album_name'),
            '/album/photo/' . $comment->sub_id . '/comments',
            $comment->user_id,
            (string) ($attributes['author_name'] ?? ''),
            $comment->text,
            $comment->time,
        );
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\CommentFeed;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Comments\Application\CommentsService;
use Johncms\Comments\Application\DTO\LatestCommentDTO;
use Johncms\Modules\Album\Application\Comments\AlbumCommentSubject;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
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
        private CommentsService $comments,
    ) {
    }

    public function latestComments(int $limit): array
    {
        if (! $this->moderates()) {
            return [];
        }

        return array_map($this->toFeed(...), $this->comments->latest(AlbumCommentSubject::TYPE, $limit, 0));
    }

    public function countComments(): int
    {
        return $this->moderates() ? $this->comments->countOfType(AlbumCommentSubject::TYPE) : 0;
    }

    private function moderates(): bool
    {
        return $this->accessChecker->allows(AlbumPermissions::MODERATE);
    }

    private function toFeed(LatestCommentDTO $latest): FeedComment
    {
        return new FeedComment(
            section: d__('album', 'Photo albums'),
            subject: $latest->subject->title,
            url: $latest->permalink(),
            authorId: $latest->authorId,
            authorName: $latest->comment->author->name ?? $latest->comment->authorName,
            text: $latest->html,
            time: $latest->writtenAt,
        );
    }
}

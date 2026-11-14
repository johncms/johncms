<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\CommentFeed;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Comments\Application\CommentsService;
use Johncms\Comments\Application\DTO\LatestCommentDTO;
use Johncms\Modules\Library\Application\Comments\LibraryCommentSubject;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;
use Johncms\View\CommentFeed\FeedComment;

/**
 * The comments on the articles, for the moderator of the library.
 */
final readonly class LibraryCommentFeedSource implements CommentFeedSourceInterface
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

        return array_map($this->toFeed(...), $this->comments->latest(LibraryCommentSubject::TYPE, $limit, 0));
    }

    public function countComments(): int
    {
        return $this->moderates() ? $this->comments->countOfType(LibraryCommentSubject::TYPE) : 0;
    }

    private function moderates(): bool
    {
        return $this->accessChecker->allows(LibraryPermissions::MODERATE);
    }

    private function toFeed(LatestCommentDTO $latest): FeedComment
    {
        return new FeedComment(
            section: d__('library', 'Library'),
            subject: $latest->subject->title,
            url: $latest->permalink(),
            authorId: $latest->authorId,
            authorName: $latest->comment->author->name ?? $latest->comment->authorName,
            text: $latest->html,
            time: $latest->writtenAt,
        );
    }
}

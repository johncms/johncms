<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\CommentFeed;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;
use Johncms\View\CommentFeed\FeedComment;

/**
 * The comments on the files, for the moderator of the downloads.
 */
final readonly class DownloadsCommentFeedSource implements CommentFeedSourceInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private DownloadFileRepositoryInterface $files,
    ) {
    }

    public function latestComments(int $limit): array
    {
        if (! $this->moderates()) {
            return [];
        }

        return array_values($this->files->getCommentsReview($limit, 0)->map($this->toFeed(...))->all());
    }

    public function countComments(): int
    {
        return $this->moderates() ? $this->files->countCommentsReview() : 0;
    }

    private function moderates(): bool
    {
        return $this->accessChecker->allows(DownloadsPermissions::MODERATE);
    }

    private function toFeed(DownloadComment $comment): FeedComment
    {
        // The name the author had when writing, kept with the comment by the legacy comments.
        $attributes = unserialize((string) $comment->getAttribute('attributes'), ['allowed_classes' => false]);

        return new FeedComment(
            d__('downloads', 'Downloads'),
            (string) $comment->getAttribute('rus_name'),
            '/downloads/comments/' . $comment->getAttribute('sub_id'),
            (int) $comment->getAttribute('user_id'),
            (string) ($attributes['author_name'] ?? ''),
            (string) $comment->getAttribute('text'),
            (int) $comment->getAttribute('time'),
        );
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\News\Application\DTO\NewsCommentPageDTO;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;
use Johncms\View\CommentFeed\FeedComment;

interface NewsDiscussionServiceInterface
{
    public function comments(int $itemId, int $limit = 20, int $offset = 0): NewsCommentPageDTO;

    /** @param list<int> $files
     * @param array<string, mixed> $metadata Captured by the HTTP controller, never taken from posted JSON.
     */
    public function addComment(int $itemId, string $text, array $files = [], array $metadata = []): int;

    public function deleteComment(int $commentId): void;

    public function vote(int $itemId, int $vote): NewsDiscussionStats;

    /** @return list<FeedComment> */
    public function latestFeed(int $limit): array;

    public function countFeed(): int;
}

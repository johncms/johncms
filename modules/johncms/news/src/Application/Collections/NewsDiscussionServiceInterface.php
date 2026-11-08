<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\DTO\NewsCommentPageDTO;
use Johncms\Modules\News\Application\DTO\NewsCommentViewDTO;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;
use Johncms\View\CommentFeed\FeedComment;

interface NewsDiscussionServiceInterface
{
    public function comments(int $itemId, int $limit = 20, int $offset = 0): NewsCommentPageDTO;

    /**
     * A page of the comments of an article already read for this visitor: the article is not
     * checked again, and the comments are not counted — the article knows how many it has.
     *
     * @return list<NewsCommentViewDTO>
     */
    public function articleComments(NewsArticleViewDTO $article, int $limit, int $offset): array;

    /** @param list<int> $files
     * @param array<string, mixed> $metadata Captured by the HTTP controller, never taken from posted JSON.
     */
    public function addComment(int $itemId, string $text, array $files = [], array $metadata = []): int;

    /** @return int The article the comment belonged to. */
    public function deleteComment(int $commentId): int;

    public function vote(int $itemId, int $vote): NewsDiscussionStats;

    /** @return list<FeedComment> */
    public function latestFeed(int $limit): array;

    public function countFeed(): int;
}

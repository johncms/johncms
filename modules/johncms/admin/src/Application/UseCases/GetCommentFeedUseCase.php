<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\View\CommentFeed\CommentFeed;
use Johncms\View\CommentFeed\FeedComment;

/**
 * The comments of every module in one list, the newest first, as far as the visitor moderates them.
 */
final readonly class GetCommentFeedUseCase
{
    public function __construct(
        private CommentFeed $feed,
    ) {
    }

    public function count(): int
    {
        return $this->feed->count();
    }

    /**
     * @return list<FeedComment>
     */
    public function getPage(int $limit, int $offset): array
    {
        return $this->feed->page($limit, $offset);
    }
}

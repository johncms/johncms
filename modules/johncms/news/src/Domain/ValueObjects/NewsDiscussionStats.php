<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\ValueObjects;

final readonly class NewsDiscussionStats
{
    public function __construct(public int $comments = 0, public int $rating = 0, public int $currentVote = 0)
    {
    }
}

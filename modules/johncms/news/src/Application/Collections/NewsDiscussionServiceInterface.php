<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;

/** The votes on the articles. Their comments are those of the core (NewsCommentSubject). */
interface NewsDiscussionServiceInterface
{
    public function vote(int $itemId, int $vote): NewsDiscussionStats;
}

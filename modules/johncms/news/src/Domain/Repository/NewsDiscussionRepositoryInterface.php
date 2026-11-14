<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;

/** The votes on the articles. */
interface NewsDiscussionRepositoryInterface
{
    /**
     * The rating of each article and the vote of the user on it; the comments are counted by the
     * core and left at zero here.
     *
     * @param list<int> $ids
     * @return array<int, NewsDiscussionStats>
     */
    public function stats(array $ids, int $userId, bool $current = false): array;

    public function vote(int $itemId, int $userId, int $vote): void;

    public function deleteVotesForItem(int $itemId): void;
}

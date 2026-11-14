<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Johncms\Modules\News\Domain\Models\NewsItemVote;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;

final class EloquentNewsDiscussionRepository implements NewsDiscussionRepositoryInterface
{
    public function stats(array $ids, int $userId, bool $current = false): array
    {
        if ($ids === []) {
            return [];
        }
        $ratingsQuery = NewsItemVote::query()->whereIn('item_id', $ids);
        $votesQuery = NewsItemVote::query()->whereIn('item_id', $ids)->where('user_id', $userId);
        if ($current) {
            $ratingsQuery->lockForUpdate();
            $votesQuery->lockForUpdate();
        }
        $ratings = $ratingsQuery->selectRaw('item_id, SUM(vote) AS aggregate')->groupBy('item_id')->pluck('aggregate', 'item_id');
        $votes = $userId > 0 ? $votesQuery->pluck('vote', 'item_id')->all() : [];
        $result = [];
        foreach ($ids as $id) {
            $result[$id] = new NewsDiscussionStats(0, (int) ($ratings[$id] ?? 0), (int) ($votes[$id] ?? 0));
        }
        return $result;
    }

    public function vote(int $itemId, int $userId, int $vote): void
    {
        /** @var NewsItemVote|null $existing */
        $existing = NewsItemVote::query()->where('item_id', $itemId)->where('user_id', $userId)->lockForUpdate()->first();
        if ($existing !== null) {
            $existing->update(['vote' => $vote]);
        } else {
            NewsItemVote::query()->create(['item_id' => $itemId, 'user_id' => $userId, 'vote' => $vote]);
        }
    }

    public function deleteVotesForItem(int $itemId): void
    {
        NewsItemVote::query()->where('item_id', $itemId)->delete();
    }
}

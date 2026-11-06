<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Johncms\Modules\News\Domain\Models\NewsItemComment;
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
        $commentsQuery = NewsItemComment::query()->whereNull('deleted_at')->whereIn('item_id', $ids);
        $ratingsQuery = NewsItemVote::query()->whereIn('item_id', $ids);
        $votesQuery = NewsItemVote::query()->whereIn('item_id', $ids)->where('user_id', $userId);
        if ($current) {
            $commentsQuery->lockForUpdate();
            $ratingsQuery->lockForUpdate();
            $votesQuery->lockForUpdate();
        }
        $comments = $commentsQuery->selectRaw('item_id, COUNT(*) AS aggregate')->groupBy('item_id')->pluck('aggregate', 'item_id');
        $ratings = $ratingsQuery->selectRaw('item_id, SUM(vote) AS aggregate')->groupBy('item_id')->pluck('aggregate', 'item_id');
        $votes = $userId > 0 ? $votesQuery->pluck('vote', 'item_id')->all() : [];
        $result = [];
        foreach ($ids as $id) {
            $result[$id] = new NewsDiscussionStats((int) ($comments[$id] ?? 0), (int) ($ratings[$id] ?? 0), (int) ($votes[$id] ?? 0));
        }
        return $result;
    }

    public function countComments(int $itemId): int
    {
        return NewsItemComment::query()->whereNull('deleted_at')->where('item_id', $itemId)->count();
    }

    public function comments(int $itemId, int $limit, int $offset): array
    {
        /** @var list<NewsItemComment> $rows */
        $rows = NewsItemComment::query()->whereNull('deleted_at')->where('item_id', $itemId)->orderBy('id')->limit($limit)->offset($offset)->get()->all();
        return $rows;
    }

    public function findComment(int $id, bool $current = false): ?NewsItemComment
    {
        $query = NewsItemComment::query()->whereNull('deleted_at')->whereNotNull('item_id');
        if ($current) {
            $query->lockForUpdate();
        }
        /** @var NewsItemComment|null $comment */
        $comment = $query->find($id);
        return $comment;
    }

    public function addComment(int $itemId, int $userId, string $text, array $metadata, array $files): int
    {
        $comment = NewsItemComment::query()->create([
            'item_id' => $itemId, 'user_id' => $userId, 'text' => $text,
            'user_data' => $metadata, 'attached_files' => $files, 'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        return $comment->id;
    }

    public function deleteComment(int $id): array
    {
        $comment = $this->findComment(id: $id, current: true);
        if ($comment === null) {
            return [];
        }
        $files = $this->ids($comment->attached_files ?? []);
        $comment->update(['attached_files' => [], 'deleted_at' => gmdate('Y-m-d H:i:s')]);
        return $files;
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

    public function deleteForItem(int $itemId): array
    {
        $files = [];
        foreach (NewsItemComment::query()->where('item_id', $itemId)->lockForUpdate()->get() as $comment) {
            $files = [...$files, ...$this->ids($comment->attached_files ?? [])];
        }
        NewsItemComment::query()->where('item_id', $itemId)->delete();
        NewsItemVote::query()->where('item_id', $itemId)->delete();
        return array_values(array_unique($files));
    }

    public function referencesFile(int $fileId): bool
    {
        foreach (NewsItemComment::query()->where('attached_files', 'like', '%' . $fileId . '%')->cursor() as $comment) {
            if (in_array($fileId, $this->ids($comment->attached_files ?? []), true)) {
                return true;
            }
        }
        return false;
    }

    public function latestComments(int $limit): array
    {
        /** @var list<NewsItemComment> $rows */
        $rows = NewsItemComment::query()->whereNull('deleted_at')->whereNotNull('item_id')->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get()->all();
        return $rows;
    }

    public function countAllComments(): int
    {
        return NewsItemComment::query()->whereNull('deleted_at')->whereNotNull('item_id')->count();
    }

    /** @param array<mixed> $values
     * @return list<int>
     */
    private function ids(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if ((is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && $value !== '0')) {
                $ids[] = (int) $value;
            }
        }
        return array_values(array_unique($ids));
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use Johncms\Modules\News\Domain\Models\NewsItemComment;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;

interface NewsDiscussionRepositoryInterface
{
    /** @param list<int> $ids
     * @return array<int, NewsDiscussionStats>
     */
    public function stats(array $ids, int $userId, bool $current = false): array;

    public function countComments(int $itemId): int;

    /** @return list<NewsItemComment> */
    public function comments(int $itemId, int $limit, int $offset): array;

    public function findComment(int $id, bool $current = false): ?NewsItemComment;

    /** @param array<string, mixed> $metadata
     * @param list<int> $files
     */
    public function addComment(int $itemId, int $userId, string $text, array $metadata, array $files): int;

    /** @return list<int> */
    public function deleteComment(int $id): array;

    public function vote(int $itemId, int $userId, int $vote): void;

    /** @return list<int> IDs of files to check after commit. */
    public function deleteForItem(int $itemId): array;

    public function referencesFile(int $fileId): bool;

    /** @return list<NewsItemComment> */
    public function latestComments(int $limit): array;

    public function countAllComments(): int;
}

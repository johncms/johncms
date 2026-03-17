<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Query\ForumFileCountQuery;
use Johncms\Modules\Forum\Domain\Query\ForumFileListingQuery;

interface ForumFileRepositoryInterface
{
    public function save(ForumFile $file): void;

    public function countAll(): int;

    public function hasFilesForPost(int $postId): bool;

    public function findById(int $fileId): ?ForumFile;

    public function findByIdAndPostId(int $fileId, int $postId): ?ForumFile;

    public function deleteById(int $fileId): void;

    public function deleteByPostId(int $postId): void;

    public function markDeletedByPostId(int $postId): void;

    public function restoreByPostId(int $postId): void;

    /**
     * @return ForumFile[]
     */
    public function getByPostId(int $postId): array;

    /**
     * @return ForumFile[]
     */
    public function getByTopicId(int $topicId): array;

    public function markDeletedByTopicId(int $topicId): void;

    public function deleteByTopicId(int $topicId): void;

    public function countForListing(ForumFileCountQuery $query): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getListingItems(ForumFileListingQuery $query): array;

    public function countByQuery(ForumFileCountQuery $query): int;
}

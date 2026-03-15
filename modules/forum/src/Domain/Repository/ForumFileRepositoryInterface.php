<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumFile;

interface ForumFileRepositoryInterface
{
    public function save(ForumFile $file): void;

    public function hasFilesForPost(int $postId): bool;

    /**
     * @return ForumFile[]
     */
    public function getByTopicId(int $topicId): array;

    public function markDeletedByTopicId(int $topicId): void;

    public function deleteByTopicId(int $topicId): void;
}

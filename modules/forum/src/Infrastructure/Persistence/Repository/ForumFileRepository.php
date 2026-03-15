<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;

final class ForumFileRepository implements ForumFileRepositoryInterface
{
    public function save(ForumFile $file): void
    {
        $file->save();
    }

    public function getByTopicId(int $topicId): array
    {
        return ForumFile::query()
            ->where('topic', $topicId)
            ->get()
            ->all();
    }

    public function hasFilesForPost(int $postId): bool
    {
        return ForumFile::query()
            ->where('post', $postId)
            ->exists();
    }

    public function markDeletedByTopicId(int $topicId): void
    {
        ForumFile::query()
            ->where('topic', $topicId)
            ->update(['del' => 1]);
    }

    public function deleteByTopicId(int $topicId): void
    {
        ForumFile::query()
            ->where('topic', $topicId)
            ->delete();
    }
}

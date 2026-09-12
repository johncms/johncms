<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Repository\HiddenForumRepositoryInterface;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;

final class HiddenForumRepository implements HiddenForumRepositoryInterface
{
    public function countTopics(?int $userId, ?int $sectionId): int
    {
        return $this->topicsQuery($userId, $sectionId)->count();
    }

    /**
     * @return Collection<int, ForumTopic>
     */
    public function getTopics(?int $userId, ?int $sectionId, int $limit, int $offset): Collection
    {
        return $this->topicsQuery($userId, $sectionId)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function topicAttachmentFilenames(?int $userId, ?int $sectionId): array
    {
        $topicIds = $this->topicsQuery($userId, $sectionId)->pluck('id')->all();

        return $topicIds === []
            ? []
            : ForumFile::query()->whereIn('topic', $topicIds)->pluck('filename')->all();
    }

    public function deleteTopics(?int $userId, ?int $sectionId): void
    {
        $topicIds = $this->topicsQuery($userId, $sectionId)->pluck('id')->all();
        if ($topicIds === []) {
            return;
        }

        ForumFile::query()->whereIn('topic', $topicIds)->delete();
        ForumMessage::query()->whereIn('topic_id', $topicIds)->delete();
        ForumTopic::query()->whereIn('id', $topicIds)->delete();
    }

    public function countPosts(?int $topicId, ?int $userId): int
    {
        return $this->postsQuery($topicId, $userId)->count();
    }

    /**
     * @return Collection<int, ForumMessage>
     */
    public function getPosts(?int $topicId, ?int $userId, int $limit, int $offset): Collection
    {
        return $this->postsQuery($topicId, $userId)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function postAttachmentFilenames(?int $topicId, ?int $userId): array
    {
        $postIds = $this->postsQuery($topicId, $userId)->pluck('id')->all();

        return $postIds === []
            ? []
            : ForumFile::query()->whereIn('post', $postIds)->pluck('filename')->all();
    }

    public function deletePosts(?int $topicId, ?int $userId): void
    {
        $postIds = $this->postsQuery($topicId, $userId)->pluck('id')->all();
        if ($postIds === []) {
            return;
        }

        ForumFile::query()->whereIn('post', $postIds)->delete();
        ForumMessage::query()->whereIn('id', $postIds)->delete();
    }

    /**
     * @return Builder<ForumTopic>
     */
    private function topicsQuery(?int $userId, ?int $sectionId): Builder
    {
        return ForumTopic::query()
            ->where('deleted', 1)
            ->when($userId !== null, fn (Builder $q) => $q->where('user_id', $userId))
            ->when($sectionId !== null, fn (Builder $q) => $q->where('section_id', $sectionId));
    }

    /**
     * @return Builder<ForumMessage>
     */
    private function postsQuery(?int $topicId, ?int $userId): Builder
    {
        return ForumMessage::query()
            ->where('deleted', 1)
            ->when($topicId !== null, fn (Builder $q) => $q->where('topic_id', $topicId))
            ->when($userId !== null, fn (Builder $q) => $q->where('user_id', $userId));
    }
}

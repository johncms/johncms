<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Repository\HiddenForumRepositoryInterface;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;

final readonly class ManageHiddenForumUseCase
{
    public function __construct(
        private HiddenForumRepositoryInterface $repository,
        private ForumAttachmentStorage $attachments,
    ) {
    }

    public function countTopics(?int $userId, ?int $sectionId): int
    {
        return $this->repository->countTopics($userId, $sectionId);
    }

    /**
     * @return Collection<int, \Johncms\Modules\Forum\Domain\Models\ForumTopic>
     */
    public function topicsPage(?int $userId, ?int $sectionId, int $limit, int $offset): Collection
    {
        return $this->repository->getTopics($userId, $sectionId, $limit, $offset);
    }

    public function countPosts(?int $topicId, ?int $userId): int
    {
        return $this->repository->countPosts($topicId, $userId);
    }

    /**
     * @return Collection<int, \Johncms\Modules\Forum\Domain\Models\ForumMessage>
     */
    public function postsPage(?int $topicId, ?int $userId, int $limit, int $offset): Collection
    {
        return $this->repository->getPosts($topicId, $userId, $limit, $offset);
    }

    public function purgeTopics(?int $userId, ?int $sectionId): void
    {
        $this->unlinkAll($this->repository->topicAttachmentFilenames($userId, $sectionId));
        $this->repository->deleteTopics($userId, $sectionId);
    }

    public function purgePosts(?int $topicId, ?int $userId): void
    {
        $this->unlinkAll($this->repository->postAttachmentFilenames($topicId, $userId));
        $this->repository->deletePosts($topicId, $userId);
    }

    /**
     * @param list<string> $filenames
     */
    private function unlinkAll(array $filenames): void
    {
        foreach ($filenames as $filename) {
            $this->attachments->delete((string) $filename);
        }
    }
}

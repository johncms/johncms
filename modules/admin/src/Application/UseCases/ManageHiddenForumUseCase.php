<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Repository\HiddenForumRepositoryInterface;

final readonly class ManageHiddenForumUseCase
{
    public function __construct(
        private HiddenForumRepositoryInterface $repository,
    ) {
    }

    public function topics(?int $userId, ?int $sectionId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginateTopics($userId, $sectionId, $page, $perPage);
    }

    public function posts(?int $topicId, ?int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginatePosts($topicId, $userId, $page, $perPage);
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
            @unlink(UPLOAD_PATH . 'forum/attach/' . $filename);
        }
    }
}

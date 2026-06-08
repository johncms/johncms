<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface HiddenForumRepositoryInterface
{
    public function paginateTopics(?int $userId, ?int $sectionId, int $page, int $perPage): LengthAwarePaginator;

    /**
     * @return list<string>
     */
    public function topicAttachmentFilenames(?int $userId, ?int $sectionId): array;

    public function deleteTopics(?int $userId, ?int $sectionId): void;

    public function paginatePosts(?int $topicId, ?int $userId, int $page, int $perPage): LengthAwarePaginator;

    /**
     * @return list<string>
     */
    public function postAttachmentFilenames(?int $topicId, ?int $userId): array;

    public function deletePosts(?int $topicId, ?int $userId): void;
}

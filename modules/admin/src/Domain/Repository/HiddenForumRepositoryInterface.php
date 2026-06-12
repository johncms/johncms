<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;

interface HiddenForumRepositoryInterface
{
    public function countTopics(?int $userId, ?int $sectionId): int;

    /**
     * @return Collection<int, ForumTopic>
     */
    public function getTopics(?int $userId, ?int $sectionId, int $limit, int $offset): Collection;

    /**
     * @return list<string>
     */
    public function topicAttachmentFilenames(?int $userId, ?int $sectionId): array;

    public function deleteTopics(?int $userId, ?int $sectionId): void;

    public function countPosts(?int $topicId, ?int $userId): int;

    /**
     * @return Collection<int, ForumMessage>
     */
    public function getPosts(?int $topicId, ?int $userId, int $limit, int $offset): Collection;

    /**
     * @return list<string>
     */
    public function postAttachmentFilenames(?int $topicId, ?int $userId): array;

    public function deletePosts(?int $topicId, ?int $userId): void;
}

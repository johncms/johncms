<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumMessage;

interface ForumMessageRepositoryInterface
{
    public function findById(int $id): ?ForumMessage;

    public function deleteByTopicId(int $topicId): void;
}

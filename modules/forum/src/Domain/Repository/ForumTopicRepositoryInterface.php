<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;

interface ForumTopicRepositoryInterface
{
    public function findActiveById(int $topicId): ?ForumTopic;

    public function markHasPoll(int $topicId): void;
}

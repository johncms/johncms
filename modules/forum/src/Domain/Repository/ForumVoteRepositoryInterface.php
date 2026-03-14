<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumVote;

interface ForumVoteRepositoryInterface
{
    public function topicHasPoll(int $topicId): bool;

    public function save(ForumVote $vote): void;
}

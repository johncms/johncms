<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumVote;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final class ForumVoteRepository implements ForumVoteRepositoryInterface
{
    public function topicHasPoll(int $topicId): bool
    {
        return ForumVote::query()
            ->where('type', 1)
            ->where('topic', $topicId)
            ->exists();
    }

    public function save(ForumVote $vote): void
    {
        $vote->save();
    }
}

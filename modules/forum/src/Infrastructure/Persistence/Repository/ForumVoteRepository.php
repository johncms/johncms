<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumVote;
use Johncms\Modules\Forum\Domain\Models\ForumVoteUser;
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

    public function findPollByTopic(int $topicId): ?ForumVote
    {
        return ForumVote::query()
            ->where('type', 1)
            ->where('topic', $topicId)
            ->first();
    }

    public function getAnswersByTopic(int $topicId): array
    {
        return ForumVote::query()
            ->where('type', 2)
            ->where('topic', $topicId)
            ->orderBy('id')
            ->get()
            ->all();
    }

    public function countAnswersByTopic(int $topicId): int
    {
        return ForumVote::query()
            ->where('type', 2)
            ->where('topic', $topicId)
            ->count();
    }

    public function findAnswerById(int $answerId): ?ForumVote
    {
        return ForumVote::query()
            ->where('type', 2)
            ->where('id', $answerId)
            ->first();
    }

    public function countAnswerVotes(int $answerId, int $topicId): int
    {
        return ForumVoteUser::query()
            ->where('vote', $answerId)
            ->where('topic', $topicId)
            ->count();
    }

    public function deleteAnswerVotes(int $answerId, int $topicId): void
    {
        ForumVoteUser::query()
            ->where('vote', $answerId)
            ->where('topic', $topicId)
            ->delete();
    }

    public function deleteAnswer(ForumVote $answer): void
    {
        $answer->delete();
    }
}

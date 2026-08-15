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

    public function findPollByTopicWithAnswers(int $topicId): ?ForumVote
    {
        return ForumVote::query()
            ->voteUser()
            ->with('answers')
            ->where('type', 1)
            ->where('topic', $topicId)
            ->first();
    }

    public function countUsersByTopic(int $topicId): int
    {
        return ForumVoteUser::query()
            ->where('topic', $topicId)
            ->count();
    }

    public function getUsersByTopic(int $topicId, int $start, int $limit): array
    {
        return ForumVoteUser::query()
            ->leftJoin('users', 'cms_forum_vote_users.user', '=', 'users.id')
            ->where('cms_forum_vote_users.topic', $topicId)
            ->select([
                'cms_forum_vote_users.*',
                'users.lastdate',
                'users.name',
                'users.sex',
                'users.status',
                'users.datereg',
                'users.id',
                'users.ip',
                'users.ip_via_proxy',
                'users.browser',
            ])
            ->offset($start)
            ->limit($limit)
            ->get()
            ->map(static fn (ForumVoteUser $voteUser): array => $voteUser->toArray())
            ->all();
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

    public function deleteVotesByTopic(int $topicId): void
    {
        ForumVote::query()
            ->where('topic', $topicId)
            ->delete();
    }

    public function deleteVoteUsersByTopic(int $topicId): void
    {
        ForumVoteUser::query()
            ->where('topic', $topicId)
            ->delete();
    }

    public function hasUserVoted(int $topicId, int $userId): bool
    {
        return ForumVoteUser::query()
            ->where('topic', $topicId)
            ->where('user', $userId)
            ->exists();
    }

    public function addUserVote(int $topicId, int $userId, int $voteId): void
    {
        ForumVoteUser::query()->create(
            [
                'topic' => $topicId,
                'user'  => $userId,
                'vote'  => $voteId,
            ]
        );
    }

    public function incrementAnswerCount(int $voteId): void
    {
        ForumVote::query()
            ->where('id', $voteId)
            ->increment('count');
    }

    public function incrementPollCount(int $topicId): void
    {
        ForumVote::query()
            ->where('topic', $topicId)
            ->where('type', 1)
            ->increment('count');
    }
}

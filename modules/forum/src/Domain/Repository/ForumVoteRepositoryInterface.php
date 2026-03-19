<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumVote;

interface ForumVoteRepositoryInterface
{
    public function topicHasPoll(int $topicId): bool;

    public function save(ForumVote $vote): void;

    public function findPollByTopic(int $topicId): ?ForumVote;

    public function findPollByTopicWithAnswers(int $topicId): ?ForumVote;

    public function countUsersByTopic(int $topicId): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUsersByTopic(int $topicId, int $start, int $limit): array;

    /**
     * @return ForumVote[]
     */
    public function getAnswersByTopic(int $topicId): array;

    public function countAnswersByTopic(int $topicId): int;

    public function findAnswerById(int $answerId): ?ForumVote;

    public function countAnswerVotes(int $answerId, int $topicId): int;

    public function deleteAnswerVotes(int $answerId, int $topicId): void;

    public function deleteAnswer(ForumVote $answer): void;

    public function deleteVotesByTopic(int $topicId): void;

    public function deleteVoteUsersByTopic(int $topicId): void;

    public function hasUserVoted(int $topicId, int $userId): bool;

    public function addUserVote(int $topicId, int $userId, int $voteId): void;

    public function incrementAnswerCount(int $voteId): void;

    public function incrementPollCount(int $topicId): void;
}

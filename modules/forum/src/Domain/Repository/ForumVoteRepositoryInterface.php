<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumVote;

interface ForumVoteRepositoryInterface
{
    public function topicHasPoll(int $topicId): bool;

    public function save(ForumVote $vote): void;

    public function findPollByTopic(int $topicId): ?ForumVote;

    /**
     * @return ForumVote[]
     */
    public function getAnswersByTopic(int $topicId): array;

    public function countAnswersByTopic(int $topicId): int;

    public function findAnswerById(int $answerId): ?ForumVote;

    public function countAnswerVotes(int $answerId, int $topicId): int;

    public function deleteAnswerVotes(int $answerId, int $topicId): void;

    public function deleteAnswer(ForumVote $answer): void;
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class DeleteVoteAnswerUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
    ) {
    }

    public function execute(int $topicId, int $answerId): bool
    {
        $answer = $this->voteRepository->findAnswerById($answerId);
        if ($answer === null || (int) $answer->topic !== $topicId) {
            return false;
        }

        $countAnswers = $this->voteRepository->countAnswersByTopic($topicId);
        if ($countAnswers <= 2) {
            return false;
        }

        $votesCount = $this->voteRepository->countAnswerVotes($answerId, $topicId);
        $poll = $this->voteRepository->findPollByTopic($topicId);

        $this->voteRepository->deleteAnswer($answer);
        $this->voteRepository->deleteAnswerVotes($answerId, $topicId);

        if ($poll !== null && $votesCount > 0) {
            $poll->count = max(0, (int) $poll->count - $votesCount);
            $this->voteRepository->save($poll);
        }

        return true;
    }
}

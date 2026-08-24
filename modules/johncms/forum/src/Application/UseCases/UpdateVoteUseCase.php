<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Models\ForumVote;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class UpdateVoteUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
    ) {
    }

    /**
     * @param array<int, string> $existingAnswers
     * @param string[] $newAnswers
     */
    public function execute(int $topicId, string $pollName, array $existingAnswers, array $newAnswers): void
    {
        $poll = $this->voteRepository->findPollByTopic($topicId);
        if ($poll === null) {
            throw new ForumValidationException('Poll not found.');
        }

        if ($pollName !== '') {
            $poll->name = $pollName;
            $this->voteRepository->save($poll);
        }

        $answers = $this->voteRepository->getAnswersByTopic($topicId);
        $answersById = [];
        foreach ($answers as $answer) {
            $answersById[(int) $answer->id] = $answer;
        }

        foreach ($existingAnswers as $answerId => $answerName) {
            if ($answerName === '') {
                continue;
            }

            if (! isset($answersById[$answerId])) {
                continue;
            }

            $answer = $answersById[$answerId];
            $answer->name = $answerName;
            $this->voteRepository->save($answer);
        }

        foreach ($newAnswers as $answerName) {
            if ($answerName === '') {
                continue;
            }

            $vote = new ForumVote();
            $vote->name = $answerName;
            $vote->type = 2;
            $vote->topic = $topicId;
            $this->voteRepository->save($vote);
        }
    }
}

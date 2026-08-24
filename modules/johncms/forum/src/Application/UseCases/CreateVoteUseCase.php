<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Models\ForumVote;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class CreateVoteUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    /**
     * @param string[] $answers
     */
    public function execute(int $topicId, string $pollName, array $answers): void
    {
        $timestamp = time();

        $poll = new ForumVote();
        $poll->name = $pollName;
        $poll->time = $timestamp;
        $poll->type = 1;
        $poll->topic = $topicId;
        $this->voteRepository->save($poll);

        foreach ($answers as $answer) {
            $vote = new ForumVote();
            $vote->name = $answer;
            $vote->type = 2;
            $vote->topic = $topicId;
            $this->voteRepository->save($vote);
        }

        $this->topicRepository->markHasPoll($topicId);
    }
}

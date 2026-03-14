<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\EditVoteAnswerDTO;
use Johncms\Modules\Forum\Application\DTO\EditVoteContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\EditVoteWrongDataException;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class GetEditVoteContextUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
    ) {
    }

    public function execute(int $topicId): EditVoteContextDTO
    {
        $poll = $this->voteRepository->findPollByTopic($topicId);
        if ($poll === null) {
            throw new EditVoteWrongDataException('Poll not found.');
        }

        $answers = [];
        foreach ($this->voteRepository->getAnswersByTopic($topicId) as $answer) {
            $answers[] = new EditVoteAnswerDTO(
                id: $answer->id,
                name: $answer->name,
            );
        }

        return new EditVoteContextDTO(
            topicId: $topicId,
            pollName: $poll->name,
            answers: $answers,
            savedVote: count($answers),
        );
    }
}

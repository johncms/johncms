<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\EditVoteAnswerDTO;
use Johncms\Modules\Forum\Application\DTO\EditVoteContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;

final readonly class GetEditVoteContextUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(int $topicId): EditVoteContextDTO
    {
        if (! $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE)) {
            throw new ForumAccessDeniedException('Access denied to edit poll.');
        }

        $poll = $this->voteRepository->findPollByTopic($topicId);
        if ($poll === null) {
            throw new ForumValidationException('Poll not found.');
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

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\SubmitVoteContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class GetSubmitVoteContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumVoteRepositoryInterface $voteRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $topicId, int $voteId, int $userId): SubmitVoteContextDTO
    {
        if (! $this->currentUser->isValid()) {
            throw new ForumAccessDeniedException('Access denied to submit vote.');
        }

        $topic = $this->topicRepository->findActiveById($topicId);
        if ($topic === null) {
            throw new ForumValidationException('Topic not found.');
        }

        $answer = $this->voteRepository->findAnswerById($voteId);
        if ($answer === null || $answer->topic !== $topicId) {
            throw new ForumValidationException('Vote answer not found.');
        }

        if ($this->voteRepository->hasUserVoted($topicId, $userId)) {
            throw new ForumValidationException('User already voted.');
        }

        return new SubmitVoteContextDTO($topicId, $voteId);
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class SubmitVoteUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
    ) {
    }

    public function execute(int $topicId, int $voteId, int $userId): void
    {
        Capsule::connection()->transaction(function () use ($topicId, $voteId, $userId): void {
            $this->voteRepository->addUserVote($topicId, $userId, $voteId);
            $this->voteRepository->incrementAnswerCount($voteId);
            $this->voteRepository->incrementPollCount($topicId);
        });
    }
}

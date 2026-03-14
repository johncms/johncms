<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class DeleteVoteUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): void
    {
        $this->voteRepository->deleteVotesByTopic($topicId);
        $this->voteRepository->deleteVoteUsersByTopic($topicId);
        $this->topicRepository->clearHasPoll($topicId);
    }
}

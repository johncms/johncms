<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\AddVoteWrongDataException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class EnsureAddVoteAccessUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumVoteRepositoryInterface $voteRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $topicId): void
    {
        if (! ($this->currentUser->rights === 3 || $this->currentUser->rights >= 6)) {
            throw new AccessDeniedException('Access denied to add poll.');
        }

        $topic = $this->topicRepository->findActiveById($topicId);
        if ($topic === null) {
            throw new AddVoteWrongDataException('Topic not found.');
        }

        if ($this->voteRepository->topicHasPoll($topicId)) {
            throw new AddVoteWrongDataException('Topic already has poll.');
        }
    }
}

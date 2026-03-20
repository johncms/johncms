<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class EnsureDeleteVoteAccessUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $topicId): void
    {
        if (! ($this->currentUser->rights === 3 || $this->currentUser->rights >= 6)) {
            throw new ForumAccessDeniedException('Access denied to delete poll.');
        }

        if (! $this->voteRepository->topicHasPoll($topicId)) {
            throw new ForumValidationException('Poll not found.');
        }
    }
}

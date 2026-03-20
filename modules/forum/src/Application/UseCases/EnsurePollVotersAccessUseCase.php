<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class EnsurePollVotersAccessUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $topicId): void
    {
        if ($this->currentUser->rights < 7) {
            throw new ForumAccessDeniedException('Access denied to poll voters list.');
        }

        if ($this->voteRepository->findPollByTopic($topicId) === null) {
            throw new ForumValidationException('Poll not found.');
        }
    }
}

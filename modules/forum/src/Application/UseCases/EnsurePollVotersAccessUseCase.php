<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\PollVotersWrongDataException;
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
            throw new AccessDeniedException('Access denied to poll voters list.');
        }

        if ($this->voteRepository->findPollByTopic($topicId) === null) {
            throw new PollVotersWrongDataException('Poll not found.');
        }
    }
}

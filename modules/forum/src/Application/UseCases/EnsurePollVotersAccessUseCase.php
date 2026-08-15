<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;

final readonly class EnsurePollVotersAccessUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(int $topicId): void
    {
        if (! $this->accessChecker->allows(ForumPermissions::POLL_VOTERS_VIEW)) {
            throw new ForumAccessDeniedException('Access denied to poll voters list.');
        }

        if ($this->voteRepository->findPollByTopic($topicId) === null) {
            throw new ForumValidationException('Poll not found.');
        }
    }
}

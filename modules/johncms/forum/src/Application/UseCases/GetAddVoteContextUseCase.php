<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;

final readonly class GetAddVoteContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumVoteRepositoryInterface $voteRepository,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(int $topicId): int
    {
        if (! $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE)) {
            throw new ForumAccessDeniedException('Access denied to add poll.');
        }

        $topic = $this->topicRepository->findActiveById($topicId);
        if ($topic === null) {
            throw new ForumValidationException('Topic not found.');
        }

        if ($this->voteRepository->topicHasPoll($topicId)) {
            throw new ForumValidationException('Topic already has poll.');
        }

        return $topic->id;
    }
}

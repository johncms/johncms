<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Access\ForumAccess;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetNewMessageContextUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private ForumTopicRepositoryInterface $topicRepository,
        private CurrentUser $currentUser,
        private ForumAccess $forumAccess,
    ) {
    }

    public function execute(int $topicId): ForumTopic
    {
        if (
            ! $this->currentUser->isValid()
            || $this->forumAccess->isBanned()
            || ! $this->accessChecker->allows(ForumPermissions::POST)
        ) {
            throw new ForumAccessDeniedException('Access denied to post message.');
        }

        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        return $topic;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Users\User;

final readonly class GetBulkDeletePostsContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private User $currentUser,
    ) {
    }

    public function execute(int $topicId): string
    {
        if (! ($this->currentUser->rights === 3 || $this->currentUser->rights >= 6)) {
            throw new ForumAccessDeniedException('Access denied to bulk delete forum posts.');
        }

        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        return $this->topicPathService->getTopicUrl($topic);
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Users\User;

final readonly class GetPinTopicContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $topicId): ForumTopic
    {
        if (! ($this->currentUser->rights === 3 || $this->currentUser->rights >= 6)) {
            throw new ForumAccessDeniedException('Access denied to pin topic.');
        }

        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        return $topic;
    }
}

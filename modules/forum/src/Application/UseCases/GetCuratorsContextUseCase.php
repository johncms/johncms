<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Users\User;

final readonly class GetCuratorsContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
    ) {
    }

    /**
     * @return array{topic: ForumTopic, candidates: array<array{user_id:int, user_name:string}>}
     */
    public function execute(int $topicId): array
    {
        if ($this->currentUser->rights < 7) {
            throw new ForumAccessDeniedException('Access denied to manage curators.');
        }

        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        $candidates = $this->messageRepository->getTopicCuratorCandidates($topicId);

        return [
            'topic'      => $topic,
            'candidates' => $candidates,
        ];
    }
}

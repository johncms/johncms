<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetCuratorsContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumMessageRepositoryInterface $messageRepository,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    /**
     * @return array{topic: ForumTopic, candidates: array<array{user_id:int, user_name:string}>}
     */
    public function execute(int $topicId): array
    {
        if (! $this->accessChecker->allows(ForumPermissions::CURATORS_MANAGE)) {
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

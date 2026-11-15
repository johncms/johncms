<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\MoveTopicContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\TopicMoveTargets;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetMoveTopicContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private TopicMoveTargets $moveTargets,
    ) {
    }

    public function execute(int $topicId): MoveTopicContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        return new MoveTopicContextDTO(
            topic: $topic,
            targets: $this->moveTargets->forSection($topic->section_id),
        );
    }
}

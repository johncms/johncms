<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class MoveTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumSectionRepositoryInterface $sectionRepository,
    ) {
    }

    public function execute(ForumTopic $topic, int $targetSectionId): void
    {
        $targetSection = $this->sectionRepository->findById($targetSectionId);
        if ($targetSection === null || $targetSection->section_type !== 1) {
            throw new ForumNotFoundException('Section not found.');
        }

        $topic->section_id = $targetSection->id;
        $this->topicRepository->save($topic);
    }
}

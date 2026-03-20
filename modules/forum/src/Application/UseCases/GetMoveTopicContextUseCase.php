<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\MoveTopicContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetMoveTopicContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumSectionRepositoryInterface $sectionRepository,
    ) {
    }

    public function execute(int $topicId, ?int $otherCategoryId): MoveTopicContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        $topicSection = $this->sectionRepository->findById($topic->section_id);
        if ($topicSection === null) {
            throw new ForumNotFoundException('Current section not found.');
        }

        $currentCategoryId = $otherCategoryId ?? $topicSection->parent;
        $currentSection = $this->sectionRepository->findById($currentCategoryId);
        if ($currentSection === null) {
            throw new ForumNotFoundException('Category not found.');
        }

        $currentSections = $this->sectionRepository->getTopicSectionsByCategory($currentSection->id, $topicSection->id);
        $otherCategories = $this->sectionRepository->getOtherCategories($currentSection->id);

        return new MoveTopicContextDTO(
            topic: $topic,
            currentSection: $currentSection,
            currentSections: $currentSections,
            otherCategories: $otherCategories,
        );
    }
}

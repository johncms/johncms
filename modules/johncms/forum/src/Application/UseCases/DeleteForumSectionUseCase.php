<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;

final readonly class DeleteForumSectionUseCase
{
    public function __construct(
        private ForumStructureRepositoryInterface $repository,
    ) {
    }

    public function deleteEmpty(int $id): void
    {
        $this->repository->deleteSection($id);
    }

    public function moveSubsectionsAndDelete(int $categoryId, int $targetCategoryId): void
    {
        $this->repository->moveSubsectionsToCategory($categoryId, $targetCategoryId);
    }

    public function moveTopicsAndDelete(int $sectionId, int $targetSectionId): void
    {
        $this->repository->moveTopicsToSection($sectionId, $targetSectionId);
    }

    /**
     * Deletes a section together with everything inside it. The rows go here; the names of the
     * attached files are handed back so that the caller can take them off the disk as well.
     *
     * @return list<string>
     */
    public function deleteWithContent(int $id): array
    {
        $filenames = $this->repository->attachedFilenames($id);
        $this->repository->deleteSectionCascade($id);

        return $filenames;
    }
}

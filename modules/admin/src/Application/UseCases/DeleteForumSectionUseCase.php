<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\ForumStructureRepositoryInterface;

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
     * Полное удаление раздела со всем содержимым (rights 9). Возвращает имена
     * прикреплённых файлов, удалённых из БД, — чтобы контроллер стёр их физически.
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

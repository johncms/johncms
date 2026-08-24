<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

interface ForumStructureRepositoryInterface
{
    /**
     * Категории верхнего уровня (parent = 0/null) со счётчиком подразделов.
     *
     * @return Collection<int, ForumSection>
     */
    public function categories(): Collection;

    /**
     * Подразделы указанной категории со счётчиком вложенных разделов.
     *
     * @return Collection<int, ForumSection>
     */
    public function subsections(int $parentId): Collection;

    public function find(int $id): ?ForumSection;

    public function nextSort(int $parentId): int;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): void;

    public function slugExists(string $slug, int $parentId, ?int $excludeId): bool;

    public function countChildSections(int $id): int;

    public function countTopics(int $sectionId): int;

    /**
     * Категории-цели для переноса (section_type != 1), кроме указанной.
     *
     * @return Collection<int, ForumSection>
     */
    public function categoriesForMove(int $excludeId): Collection;

    /**
     * Разделы для переноса тем (тот же родитель), кроме указанного.
     *
     * @return Collection<int, ForumSection>
     */
    public function sectionsForMove(int $parentRef, int $excludeId): Collection;

    /**
     * Категории верхнего уровня, кроме указанной.
     *
     * @return Collection<int, ForumSection>
     */
    public function topLevelExcept(int $excludeId): Collection;

    public function moveSubsectionsToCategory(int $fromCategoryId, int $toCategoryId): void;

    public function moveTopicsToSection(int $fromSectionId, int $toSectionId): void;

    public function deleteSection(int $id): void;

    /**
     * Имена прикреплённых файлов раздела (для физического удаления).
     *
     * @return list<string>
     */
    public function attachedFilenames(int $sectionId): array;

    /**
     * Полностью удаляет раздел со всем содержимым (файлы-записи, посты,
     * голосования, отметки прочтения, темы, сам раздел).
     */
    public function deleteSectionCascade(int $id): void;
}

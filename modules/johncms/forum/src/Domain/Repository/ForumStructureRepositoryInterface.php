<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

interface ForumStructureRepositoryInterface
{
    /**
     * The top-level categories (parent = 0 or null), each with the number of sections in it.
     *
     * @return Collection<int, ForumSection>
     */
    public function categories(): Collection;

    /**
     * The sections of one category, each with the number of sections nested in it.
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
     * Where a category can be moved to: every category but the one being moved.
     *
     * @return Collection<int, ForumSection>
     */
    public function categoriesForMove(int $excludeId): Collection;

    /**
     * Where the topics of a section can go: the other sections of the same category.
     *
     * @return Collection<int, ForumSection>
     */
    public function sectionsForMove(int $parentRef, int $excludeId): Collection;

    /**
     * The top-level categories, except the one given.
     *
     * @return Collection<int, ForumSection>
     */
    public function topLevelExcept(int $excludeId): Collection;

    public function moveSubsectionsToCategory(int $fromCategoryId, int $toCategoryId): void;

    public function moveTopicsToSection(int $fromSectionId, int $toSectionId): void;

    public function deleteSection(int $id): void;

    /**
     * The names of the files attached inside a section, so they can be taken off the disk.
     *
     * @return list<string>
     */
    public function attachedFilenames(int $sectionId): array;

    /**
     * Deletes a section with everything in it: the file rows, the posts, the polls, the read
     * marks, the topics, and the section itself.
     */
    public function deleteSectionCascade(int $id): void;
}

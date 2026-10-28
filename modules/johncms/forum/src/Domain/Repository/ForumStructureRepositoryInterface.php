<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

interface ForumStructureRepositoryInterface
{
    /**
     * Every section of the forum in the order of sort, each with the number of sections in it and
     * of the topics in it that are not hidden.
     *
     * @return Collection<int, ForumSection>
     */
    public function tree(): Collection;

    public function find(int $id): ?ForumSection;

    public function nextSort(int $parentId): int;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ForumSection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(ForumSection $section, array $attributes): void;

    /**
     * The files of a section are filed under its parent as well; they follow it when it moves.
     */
    public function moveSectionFiles(int $sectionId, int $fromParentId, int $toParentId): void;

    public function slugExists(string $slug, int $parentId, ?int $excludeId): bool;

    public function countChildSections(int $id): int;

    /**
     * Every topic of the section, the hidden ones included.
     */
    public function countTopics(int $sectionId): int;

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

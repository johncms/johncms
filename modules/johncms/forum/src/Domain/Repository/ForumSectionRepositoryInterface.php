<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

interface ForumSectionRepositoryInterface
{
    public function findById(int $sectionId): ?ForumSection;

    public function findByParentAndSlug(int $parentId, string $slug): ?ForumSection;

    public function findWithCategoryFilesCountById(int $sectionId): ?ForumSection;

    public function findWithSectionFilesCountById(int $sectionId): ?ForumSection;

    /**
     * @return Collection<int, ForumSection>
     */
    public function getRootSectionsWithSubsections(): Collection;

    /**
     * @return Collection<int, ForumSection>
     */
    public function getChildrenWithCounts(int $parentId): Collection;

    /**
     * @return ForumSection[]
     */
    public function getTopicSectionsByCategory(int $categoryId, int $excludeSectionId): array;

    /**
     * @return ForumSection[]
     */
    public function getOtherCategories(int $excludeCategoryId): array;

    /**
     * @return Collection<int, ForumSection>
     */
    public function getAllForSitemap(): Collection;

    /**
     * @return Collection<int, ForumSection>
     */
    public function getAllOrdered(): Collection;
}

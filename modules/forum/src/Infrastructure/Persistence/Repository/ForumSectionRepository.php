<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;

final class ForumSectionRepository implements ForumSectionRepositoryInterface
{
    public function findById(int $sectionId): ?ForumSection
    {
        return ForumSection::query()->find($sectionId);
    }

    public function findByParentAndSlug(int $parentId, string $slug): ?ForumSection
    {
        return ForumSection::query()
            ->where('slug', $slug)
            ->where(static function ($query) use ($parentId): void {
                $query->where('parent', $parentId);

                if ($parentId === 0) {
                    $query->orWhereNull('parent');
                }
            })
            ->first();
    }

    public function findWithCategoryFilesCountById(int $sectionId): ?ForumSection
    {
        return ForumSection::query()
            ->withCount('categoryFiles')
            ->find($sectionId);
    }

    public function findWithSectionFilesCountById(int $sectionId): ?ForumSection
    {
        return ForumSection::query()
            ->withCount('sectionFiles')
            ->find($sectionId);
    }

    public function getRootSectionsWithSubsections(): Collection
    {
        return ForumSection::query()
            ->withCount('subsections', 'topics')
            ->with('subsections')
            ->where('parent', 0)
            ->orWhereNull('parent')
            ->orderBy('sort')
            ->get();
    }

    public function getChildrenWithCounts(int $parentId): Collection
    {
        return ForumSection::query()
            ->withCount(['subsections', 'topics'])
            ->where('parent', $parentId)
            ->orderBy('sort')
            ->get();
    }

    public function getTopicSectionsByCategory(int $categoryId, int $excludeSectionId): array
    {
        return ForumSection::query()
            ->where('parent', $categoryId)
            ->where('section_type', 1)
            ->where('id', '!=', $excludeSectionId)
            ->orderBy('sort')
            ->get()
            ->all();
    }

    public function getOtherCategories(int $excludeCategoryId): array
    {
        return ForumSection::query()
            ->where('id', '!=', $excludeCategoryId)
            ->where(static function ($query): void {
                $query->where('section_type', '!=', 1)
                    ->orWhereNull('section_type');
            })
            ->orderBy('sort')
            ->get()
            ->all();
    }

    public function getAllForSitemap(): Collection
    {
        return ForumSection::query()
            ->select(['id', 'parent', 'slug'])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function getAllOrdered(): Collection
    {
        return ForumSection::query()
            ->orderBy('sort')
            ->get();
    }
}

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
}

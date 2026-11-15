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

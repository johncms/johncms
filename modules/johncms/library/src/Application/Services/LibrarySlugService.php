<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Illuminate\Support\Str;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;

final readonly class LibrarySlugService
{
    public function generateCategorySlug(string $name, int $parentId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'section';
        }

        $slug = $baseSlug;
        $suffix = 2;
        while ($this->categorySlugExists($parentId, $slug, $excludeId)) {
            $slug = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    public function generateArticleSlug(string $name, int $catId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'article';
        }

        $slug = $baseSlug;
        $suffix = 2;
        while ($this->articleSlugExists($catId, $slug, $excludeId)) {
            $slug = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    private function categorySlugExists(int $parentId, string $slug, ?int $excludeId): bool
    {
        return LibraryCategory::query()
            ->where('parent', $parentId)
            ->where('slug', $slug)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    private function articleSlugExists(int $catId, string $slug, ?int $excludeId): bool
    {
        return LibraryText::query()
            ->where('cat_id', $catId)
            ->where('slug', $slug)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}

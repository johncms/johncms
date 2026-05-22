<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Johncms\Modules\Library\Domain\Models\LibraryCategory;

final class LibraryCategoryPathService
{
    /** @var array<int, string> */
    private array $pathCache = [];

    /** @var array<string, LibraryCategory> */
    private array $categoryByPathCache = [];

    public function findCategoryByPath(string $path): ?LibraryCategory
    {
        $normalized = trim($path, '/');
        if ($normalized === '') {
            return null;
        }

        if (isset($this->categoryByPathCache[$normalized])) {
            return $this->categoryByPathCache[$normalized];
        }

        $segments = explode('/', $normalized);
        $parentId = 0;
        $category = null;

        foreach ($segments as $segment) {
            $category = LibraryCategory::query()
                ->where('parent', $parentId)
                ->where('slug', $segment)
                ->first();

            if ($category === null) {
                return null;
            }

            $parentId = $category->id;
        }

        $this->categoryByPathCache[$normalized] = $category;
        $this->pathCache[$category->id] = $normalized;

        return $category;
    }

    public function getCategoryPath(LibraryCategory $category): string
    {
        if (isset($this->pathCache[$category->id])) {
            return $this->pathCache[$category->id];
        }

        $segments = [trim((string) $category->slug)];
        $parentId = (int) ($category->parent ?? 0);

        while ($parentId > 0) {
            $parent = LibraryCategory::query()->find($parentId);
            if ($parent === null) {
                break;
            }

            array_unshift($segments, trim((string) $parent->slug));
            $parentId = (int) ($parent->parent ?? 0);
        }

        $path = implode('/', $segments);
        $this->pathCache[$category->id] = $path;
        $this->categoryByPathCache[$path] = $category;

        return $path;
    }

    public function getCategoryUrl(LibraryCategory $category): string
    {
        return '/library/' . $this->getCategoryPath($category) . '/';
    }

    public function getCategoryUrlById(int $id): ?string
    {
        $category = LibraryCategory::query()->find($id);
        if ($category === null) {
            return null;
        }

        return $this->getCategoryUrl($category);
    }
}

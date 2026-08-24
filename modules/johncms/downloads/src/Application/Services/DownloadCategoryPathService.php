<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Repository\DownloadCategoryRepositoryInterface;

final class DownloadCategoryPathService
{
    /** @var array<int, string> */
    private array $pathCache = [];

    /** @var array<string, DownloadCategory> */
    private array $categoryByPathCache = [];

    public function __construct(
        private DownloadCategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function findCategoryByPath(string $categoryPath): ?DownloadCategory
    {
        $normalizedPath = trim($categoryPath, '/');
        if ($normalizedPath === '') {
            return null;
        }

        if (isset($this->categoryByPathCache[$normalizedPath])) {
            return $this->categoryByPathCache[$normalizedPath];
        }

        $segments = explode('/', $normalizedPath);
        $parentId = 0;
        $category = null;

        foreach ($segments as $segment) {
            $category = $this->categoryRepository->findByParentAndSlug($parentId, $segment);

            if ($category === null) {
                return null;
            }

            $parentId = $category->id;
        }

        $this->categoryByPathCache[$normalizedPath] = $category;
        $this->pathCache[$category->id] = $normalizedPath;

        return $category;
    }

    public function getCategoryPath(DownloadCategory $category): string
    {
        if (isset($this->pathCache[$category->id])) {
            return $this->pathCache[$category->id];
        }

        $segments = [trim((string) $category->slug)];
        $parentId = (int) ($category->refid ?? 0);

        while ($parentId > 0) {
            $parent = $this->categoryRepository->findById($parentId);
            if ($parent === null) {
                break;
            }

            array_unshift($segments, trim((string) $parent->slug));
            $parentId = (int) ($parent->refid ?? 0);
        }

        $path = implode('/', $segments);
        $this->pathCache[$category->id] = $path;
        $this->categoryByPathCache[$path] = $category;

        return $path;
    }

    public function getCategoryUrl(DownloadCategory $category): string
    {
        return '/downloads/' . $this->getCategoryPath($category) . '/';
    }

    public function getCategoryUrlById(int $id): ?string
    {
        $category = $this->categoryRepository->findById($id);
        if ($category === null) {
            return null;
        }

        return $this->getCategoryUrl($category);
    }
}

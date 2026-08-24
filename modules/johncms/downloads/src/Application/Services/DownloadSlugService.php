<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Illuminate\Support\Str;
use Johncms\Modules\Downloads\Domain\Repository\DownloadCategoryRepositoryInterface;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final readonly class DownloadSlugService
{
    private const RESERVED_CATEGORY_SLUGS = [
        'new',
        'top',
        'search',
        'favorites',
        'user-files',
        'load',
        'comments',
        'upload',
        'moderation',
        'edit-file',
        'delete-file',
        'edit-screen',
        'additional-files',
        'move-file',
        'import',
        'scan-dir',
        'recount',
        'top-users',
        'comments-review',
        'categories',
    ];

    public function __construct(
        private DownloadCategoryRepositoryInterface $categoryRepository,
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function generateUniqueCategorySlug(string $name, int $parentId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'section';
        }

        if (in_array($baseSlug, self::RESERVED_CATEGORY_SLUGS, true)) {
            $baseSlug .= '-section';
        }

        $slug = $baseSlug;
        $suffix = 2;
        while (
            $this->categoryRepository->findByParentAndSlug($parentId, $slug) !== null
            && ($excludeId === null || $this->categoryRepository->findByParentAndSlug($parentId, $slug)?->id !== $excludeId)
        ) {
            $slug = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    public function generateUniqueFileSlug(string $name, int $categoryId, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'file';
        }

        $slug = $baseSlug;
        $suffix = 2;
        while ($this->fileRepository->existsByCategoryAndSlug($categoryId, $slug, $excludeId)) {
            $slug = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }
}

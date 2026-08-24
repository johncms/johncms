<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Infrastructure\Persistence\Repository;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Repository\DownloadCategoryRepositoryInterface;

final class DownloadCategoryRepository implements DownloadCategoryRepositoryInterface
{
    public function findById(int $id): ?DownloadCategory
    {
        return DownloadCategory::query()->find($id);
    }

    public function findByParentAndSlug(int $parentId, string $slug): ?DownloadCategory
    {
        return DownloadCategory::query()
            ->where('refid', $parentId)
            ->where('slug', $slug)
            ->first();
    }
}

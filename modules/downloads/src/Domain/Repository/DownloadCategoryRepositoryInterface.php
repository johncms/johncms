<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Repository;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;

interface DownloadCategoryRepositoryInterface
{
    public function findById(int $id): ?DownloadCategory;

    public function findByParentAndSlug(int $parentId, string $slug): ?DownloadCategory;
}

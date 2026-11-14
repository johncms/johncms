<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;

/**
 * Counts again the files of every category, its subcategories included.
 */
final readonly class RecountCategoriesUseCase
{
    public function execute(): void
    {
        DownloadCategory::query()->each(function (DownloadCategory $category): void {
            $count = DownloadFile::query()
                ->where('type', 2)
                ->where('dir', 'like', $category->dir . '%')
                ->count();
            $category->update(['total' => $count]);
        });
    }
}

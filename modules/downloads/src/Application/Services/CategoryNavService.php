<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\NavChain;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;

final class CategoryNavService
{
    public function __construct(
        private NavChain $navChain,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function buildForFileDir(string $fileDir): void
    {
        $parts = explode('/', $fileDir);
        $dirs = [];
        $cumulative = '';
        foreach ($parts as $i => $part) {
            $cumulative = $cumulative !== '' ? $cumulative . '/' . $part : $part;
            if ($i > 2) {
                $dirs[] = $cumulative;
            }
        }

        if (empty($dirs)) {
            return;
        }

        DownloadCategory::query()
            ->whereIn('dir', $dirs)
            ->orderBy('id')
            ->each(function (DownloadCategory $cat): void {
                $this->navChain->add(htmlspecialchars($cat->rus_name), $this->categoryPathService->getCategoryUrl($cat));
            });
    }
}

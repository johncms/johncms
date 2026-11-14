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
        foreach ($this->crumbsForFileDir($fileDir) as $crumb) {
            $this->navChain->add($crumb['name'], $crumb['url']);
        }
    }

    /**
     * The categories a directory of files lies in, from the top one down.
     *
     * @return list<array{name: string, url: string}>
     */
    public function crumbsForFileDir(string $fileDir): array
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

        if ($dirs === []) {
            return [];
        }

        $crumbs = [];
        DownloadCategory::query()
            ->whereIn('dir', $dirs)
            ->orderBy('id')
            ->each(function (DownloadCategory $cat) use (&$crumbs): void {
                $crumbs[] = ['name' => (string) $cat->rus_name, 'url' => $this->categoryPathService->getCategoryUrl($cat)];
            });

        return $crumbs;
    }
}

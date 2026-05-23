<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Domain\Repository\DownloadCategoryRepositoryInterface;

final class DownloadLegacyRedirectResolver
{
    public function __construct(
        private DownloadCategoryRepositoryInterface $categoryRepository,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function resolve(array $query): ?string
    {
        $id = isset($query['id']) ? abs((int) $query['id']) : 0;
        if ($id <= 0) {
            return null;
        }

        $category = $this->categoryRepository->findById($id);
        if ($category === null) {
            return '/downloads/';
        }

        $url = $this->categoryPathService->getCategoryUrl($category);

        $page = isset($query['page']) ? abs((int) $query['page']) : 0;
        if ($page > 1) {
            $url .= '?page=' . $page;
        }

        return $url;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;

final readonly class DownloadPathController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private DownloadCategoryPathService $categoryPathService,
        private DownloadFilePathService $filePathService,
        private DownloadCategoryController $categoryController,
        private ViewFileController $viewFileController,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(string $categoryPath): string
    {
        if ($this->categoryPathService->findCategoryByPath($categoryPath) !== null) {
            return $this->categoryController->__invoke($categoryPath);
        }

        if ($this->filePathService->parseFilePath('/downloads/' . ltrim($categoryPath, '/')) !== null) {
            return $this->viewFileController->__invoke($categoryPath);
        }

        pageNotFound();
    }
}

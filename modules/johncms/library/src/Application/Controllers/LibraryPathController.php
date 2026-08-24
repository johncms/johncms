<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;

final readonly class LibraryPathController
{
    public function __construct(
        private LibraryCategoryPathService $categoryPathService,
        private LibraryArticlePathService $articlePathService,
        private SectionController $sectionController,
        private ArticleController $articleController,
    ) {
    }

    public function __invoke(string $libraryPath): ViewResponse
    {
        if ($this->categoryPathService->findCategoryByPath($libraryPath) !== null) {
            return $this->sectionController->__invoke($libraryPath);
        }

        if ($this->articlePathService->parseArticlePath('/library/' . ltrim($libraryPath, '/')) !== null) {
            return $this->articleController->__invoke($libraryPath);
        }

        pageNotFound();
    }
}

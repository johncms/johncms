<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\System\View\Render;

final readonly class LibraryPathController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private LibraryCategoryPathService $categoryPathService,
        private LibraryArticlePathService $articlePathService,
        private SectionController $sectionController,
        private ArticleController $articleController,
    ) {
        $this->controllerContext->initModule('library');
        $this->render->addFolder('libraryHelpers', MODULES_PATH . 'library/templates/helpers/');
    }

    public function __invoke(string $libraryPath): string
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

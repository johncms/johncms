<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\News\Application\Article;
use Johncms\Modules\News\Application\MetaTagsManager;
use Johncms\Modules\News\Application\Section;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class SectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private MetaTagsManager $metaTagsManager,
        private Render $render,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('news');
        $this->navChain->add(__('News'), '/news/');
    }

    /**
     * List of articles and sections
     *
     * @param Article $article
     * @param Section $section
     * @param string $category
     * @return string
     */
    public function index(Article $article, Section $section, string $category = ''): string
    {
        $section->checkPath($category);
        $current_section = $section->getLastSection();
        $this->render->addData($this->metaTagsManager->setForSection($current_section)->toArray());

        $sections = $section->getCachedSubsections($current_section);

        $pagination = $this->paginationFactory->create($article->countArticles($sections));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $articles = $pagination->getTotal() > 0
            ? $article->getArticles($sections, $pagination->getPerPage(), $pagination->getOffset())
            : new Collection();

        return $this->render->render(
            'news::public/index',
            [
                'sections'        => $section->getSections($current_section->id ?? 0),
                'articles'        => $articles,
                'pagination'      => $pagination->render(),
                'current_section' => $current_section,
            ]
        );
    }
}

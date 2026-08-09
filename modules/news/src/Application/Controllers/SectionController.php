<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\News\Application\Article;
use Johncms\Modules\News\Application\MetaTagsManager;
use Johncms\Modules\News\Application\Section;
use Johncms\NavChain;

final readonly class SectionController
{
    public function __construct(
        private NavChain $navChain,
        private MetaTagsManager $metaTagsManager,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    /**
     * List of articles and sections
     *
     * @param Article $article
     * @param Section $section
     * @param string $category
     */
    public function index(Article $article, Section $section, string $category = ''): ViewResponse
    {
        $this->navChain->add(__('News'), '/news/');

        $section->checkPath($category);
        $current_section = $section->getLastSection();

        $sections = $section->getCachedSubsections($current_section);

        $pagination = $this->paginationFactory->create($article->countArticles($sections));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $articles = $pagination->getTotal() > 0
            ? $article->getArticles($sections, $pagination->getPerPage(), $pagination->getOffset())
            : new Collection();

        return new ViewResponse(
            '@news/public/index.twig',
            $this->metaTagsManager->setForSection($current_section)->toArray() + [
                'sections'        => $section->getSections($current_section->id ?? 0),
                'articles'        => $articles,
                'pagination'      => $pagination->hasPages() ? $pagination->render() : null,
                'current_section' => $current_section,
            ]
        );
    }
}

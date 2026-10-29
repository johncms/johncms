<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\Services\NewsListControlsMapper;
use Johncms\Modules\News\Application\Services\NewsListFilterMapper;
use Johncms\Modules\News\Application\Services\NewsListRowMapper;
use Johncms\Modules\News\Application\UseCases\GetNewsListUseCase;
use Johncms\Modules\News\Application\UseCases\GetSectionTreeUseCase;
use Johncms\Modules\News\Domain\Enums\NewsListTab;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\NavChain;

/**
 * The news in the panel: every article, or every section, flat, with the path of the section
 * beside each.
 *
 * A live list, as the list of users: the script asks for the same address with
 * X-Requested-With and gets the regions of the page alone.
 */
final readonly class NewsListController
{
    public function __construct(
        private NavChain $navChain,
        private GetNewsListUseCase $getList,
        private GetSectionTreeUseCase $getTree,
        private NewsListFilterMapper $filterMapper,
        private NewsListControlsMapper $controlsMapper,
        private NewsListRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $filter = $this->filterMapper->fromQuery($request->query->all());
        $tree = $this->getTree->execute();
        $articles = $filter->tab === NewsListTab::ARTICLES;
        $pagination = $articles ? $this->paginationFactory->create($this->getList->count($filter, $tree)) : null;

        if ($pagination !== null) {
            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }
        }

        $listUrl = $pagination?->getUrl($pagination->getCurrentPage()) ?? $this->controlsMapper->url($filter);
        $sectionRows = $articles ? [] : $this->rowMapper->sectionTree($this->getList->getAllSectionsWithArticleCounts(), $tree, $filter, $listUrl);

        $data = [
            'tab'        => $filter->tab->value,
            'rows'       => $articles
                ? $this->rowMapper->articles($this->getList->getArticles($filter, $tree, $pagination->getPerPage(), $pagination->getOffset()), $tree, $listUrl)
                : $sectionRows,
            'controls'   => $this->controlsMapper->map($filter, $this->getList->tabCounts(), $tree, $articles ? $this->getList->authors() : new Collection()),
            'search'     => $filter->search,
            'total'      => $pagination?->getTotal() ?? count($sectionRows),
            'pagination' => $pagination?->render(),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@news/admin/live.twig', $data);
        }

        $title = __('News');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination?->getCurrentPage() ?? 1);
        // A new article or section goes into the section the list is narrowed to.
        $section = $filter->section ?? NewsListFilter::ROOT;

        return new ViewResponse('@news/admin/index.twig', $data + [
            'title'           => $meta->title,
            'page_title'      => $title,
            'module_menu'     => ['news' => true],
            'add_article_url' => '/admin/news/articles/new' . ($section !== NewsListFilter::ROOT ? '?section=' . $section : ''),
            'add_section_url' => '/admin/news/sections/new' . ($section !== NewsListFilter::ROOT ? '?parent=' . $section : ''),
            'settings_url'    => '/admin/news/settings/',
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}

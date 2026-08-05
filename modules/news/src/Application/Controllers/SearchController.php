<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final class SearchController
{
    protected array $config;

    public function __construct(
        private readonly ControllerContext $controllerContext,
        private readonly NavChain $navChain,
        private readonly PaginationFactory $paginationFactory,
        private readonly PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('news');
        $this->config = config('news') ?? [];
        $this->navChain->add(__('News'), '/news/');
    }

    /**
     * The search page
     *
     * @param Request $request
     */
    public function index(Request $request): ViewResponse
    {
        $page_title = __('Search');
        $this->navChain->add($page_title, '');

        $query = $request->queryParam('query');
        $articles = null;
        $pagination = null;
        if (! empty($query)) {
            $like = '%' . $query . '%';
            $pager = $this->paginationFactory->create($this->searchQuery($like)->count());

            $redirectUrl = $this->paginationGuard->redirectUrl($pager);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            if ($pager->getTotal() > 0) {
                $articles = $this->searchQuery($like)
                    ->withCount('comments')
                    ->offset($pager->getOffset())
                    ->limit($pager->getPerPage())
                    ->get();
            }
            $pagination = $pager->hasPages() ? $pager->render() : null;
        }

        return new ViewResponse(
            '@news/public/search.twig',
            [
                'title'            => $page_title,
                'page_title'       => $page_title,
                'keywords'         => $this->config['meta_keywords'] ?? '',
                'description'      => $this->config['meta_description'] ?? '',
                'query'            => (string) $query,
                'articles'         => $articles,
                'pagination'       => $pagination,
                'form_action'      => '/news/search/',
                'form_field'       => 'query',
                'form_placeholder' => __('Search query'),
            ]
        );
    }

    /**
     * The search by tags page
     *
     * @param Request $request
     */
    public function byTags(Request $request): ViewResponse
    {
        $page_title = __('Search by tags');
        $this->navChain->add($page_title, '');

        $query = $request->queryParam('tag');
        $articles = null;
        $pagination = null;
        if (! empty($query)) {
            $like = '%' . $query . '%';
            $pager = $this->paginationFactory->create($this->tagsQuery($like)->count());

            $redirectUrl = $this->paginationGuard->redirectUrl($pager);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            if ($pager->getTotal() > 0) {
                $articles = $this->tagsQuery($like)
                    ->withCount('comments')
                    ->offset($pager->getOffset())
                    ->limit($pager->getPerPage())
                    ->get();
            }
            $pagination = $pager->hasPages() ? $pager->render() : null;
        }

        return new ViewResponse(
            '@news/public/search.twig',
            [
                'title'            => $page_title,
                'page_title'       => $page_title,
                'keywords'         => $this->config['meta_keywords'],
                'description'      => $this->config['meta_description'],
                'query'            => (string) $query,
                'articles'         => $articles,
                'pagination'       => $pagination,
                'form_action'      => '/news/search_tags/',
                'form_field'       => 'tag',
                'form_placeholder' => __('Enter a tag'),
            ]
        );
    }

    /**
     * @return Builder<NewsArticle>
     */
    private function searchQuery(string $like): Builder
    {
        return (new NewsArticle())
            ->active()
            ->search()
            ->where('news_search_index.text', 'like', $like);
    }

    /**
     * @return Builder<NewsArticle>
     */
    private function tagsQuery(string $like): Builder
    {
        return (new NewsArticle())
            ->active()
            ->where('tags', 'like', $like);
    }
}

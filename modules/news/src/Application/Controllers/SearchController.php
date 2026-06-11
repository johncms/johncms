<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final class SearchController
{
    protected array $config;

    public function __construct(
        private readonly ControllerContext $controllerContext,
        private readonly NavChain $navChain,
        private readonly Render $render,
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
     * @return string
     */
    public function index(Request $request): string
    {
        $page_title = __('Search');
        $this->navChain->add($page_title, '');
        $this->render->addData(
            [
                'title'       => $page_title,
                'page_title'  => $page_title,
                'keywords'    => $this->config['meta_keywords'] ?? '',
                'description' => $this->config['meta_description'] ?? '',
            ]
        );

        $query = $request->getQuery('query');
        $articles = null;
        $pagination = '';
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
            $pagination = $pager->render();
        }

        return $this->render->render(
            'news::public/search',
            [
                'query'      => htmlspecialchars($query ?? ''),
                'articles'   => $articles,
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * The search by tags page
     *
     * @param Request $request
     * @return string
     */
    public function byTags(Request $request): string
    {
        $page_title = __('Search by tags');
        $this->navChain->add($page_title, '');
        $this->render->addData(
            [
                'title'       => $page_title,
                'page_title'  => $page_title,
                'keywords'    => $this->config['meta_keywords'],
                'description' => $this->config['meta_description'],
            ]
        );

        $query = $request->getQuery('tag');
        $articles = null;
        $pagination = '';
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
            $pagination = $pager->render();
        }

        return $this->render->render(
            'news::public/search_by_tags',
            [
                'query'      => htmlspecialchars($query ?? ''),
                'articles'   => $articles,
                'pagination' => $pagination,
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

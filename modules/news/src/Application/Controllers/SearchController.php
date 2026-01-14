<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
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
        if (! empty($query)) {
            $articles = (new NewsArticle())
                ->active()
                ->withCount('comments')
                ->search()
                ->where('news_search_index.text', 'like', '%' . $query . '%')
                ->paginate();
        }

        return $this->render->render(
            'news::public/search',
            [
                'query'    => htmlspecialchars($query ?? ''),
                'articles' => $articles ?? null,
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
        if (! empty($query)) {
            $articles = (new NewsArticle())
                ->active()
                ->withCount('comments')
                ->where('tags', 'like', '%' . $query . '%')
                ->paginate();
        }

        return $this->render->render(
            'news::public/search_by_tags',
            [
                'query'    => htmlspecialchars($query ?? ''),
                'articles' => $articles ?? null,
            ]
        );
    }
}

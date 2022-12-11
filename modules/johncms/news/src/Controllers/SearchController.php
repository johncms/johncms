<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\News\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Http\Request;
use Johncms\News\Models\NewsArticle;
use Throwable;

class SearchController extends BaseController
{
    protected string $moduleName = 'johncms/news';

    protected array $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->navChain->add(__('News'), route('news.section'));
    }

    /**
     * The search page
     *
     * @param Request $request
     * @return string
     * @throws Throwable
     */
    public function index(Request $request): string
    {
        $pageTitle = __('Search');
        $this->navChain->add($pageTitle, '');
        $this->metaTagManager->setAll($pageTitle);

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
            'johncms/news::public/search',
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
     * @throws Throwable
     */
    public function byTags(Request $request): string
    {
        $pageTitle = __('Search by tags');
        $this->navChain->add($pageTitle, '');
        $this->metaTagManager->setAll($pageTitle);

        $query = $request->getQuery('tag');
        if (! empty($query)) {
            $articles = (new NewsArticle())
                ->active()
                ->withCount('comments')
                ->where('tags', 'like', '%' . $query . '%')
                ->paginate();
        }

        return $this->render->render(
            'johncms/news::public/search_by_tags',
            [
                'query'    => htmlspecialchars($query ?? ''),
                'articles' => $articles ?? null,
            ]
        );
    }
}

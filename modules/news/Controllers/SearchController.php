<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace News\Controllers;

use Johncms\Controller\BaseController;
use Johncms\System\Http\Request;
use News\Models\NewsArticle;

class SearchController extends BaseController
{
    protected $module_name = 'news';

    protected $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->nav_chain->add(__('News'), '/news/');
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
        $this->nav_chain->add($page_title, '');
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
        $this->nav_chain->add($page_title, '');
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

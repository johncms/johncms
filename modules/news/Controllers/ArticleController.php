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

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Controller\BaseController;
use News\Models\NewsArticle;
use News\Models\NewsSection;
use News\Utils\Helpers;

class ArticleController extends BaseController
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
     * Article page
     *
     * @param string $article_code
     * @param string $category
     * @return string
     */
    public function index(string $article_code, string $category = ''): string
    {
        $current_section = null;
        if (! empty($category)) {
            $path = Helpers::checkPath($category);
            if (! empty($path)) {
                foreach ($path as $item) {
                    /** @var $item NewsSection */
                    $this->nav_chain->add($item->name, $item->url);
                }
                /** @var NewsSection $current_section */
                $current_section = $path[array_key_last($path)];
            }
        }

        try {
            $article = (new NewsArticle())->where('code', $article_code)->firstOrFail();
            // Фиксируем количество просмотров
            if (empty($_SESSION['news_viewed_articles']) || ! in_array($article->id, $_SESSION['news_viewed_articles'], true)) {
                ++$article->view_count;
                $article->save();
                $_SESSION['news_viewed_articles'][] = $article->id;
            }

            $page_title = $article->name;
            $this->nav_chain->add($page_title, $article->url);

            $this->render->addData(
                [
                    'title'       => $article->meta_title,
                    'page_title'  => $page_title,
                    'keywords'    => $article->meta_keywords,
                    'description' => $article->meta_description,
                ]
            );

            return $this->render->render(
                'news::public/article',
                [
                    'article'         => $article,
                    'current_section' => $current_section,
                ]
            );
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }
        return '';
    }
}

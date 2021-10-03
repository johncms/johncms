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
use Johncms\News\Article;
use Johncms\News\NewsMetaManager;
use Johncms\News\Section;

class ArticleController extends BaseController
{
    protected string $moduleName = 'johncms/news';

    protected array $config;

    protected NewsMetaManager $meta_tags;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->navChain->add(__('News'), '/news/');
        $this->meta_tags = new NewsMetaManager();
    }

    /**
     * Article page
     *
     * @param Section $section
     * @param Article $article
     * @param string $article_code
     * @param string $category
     * @return string
     * @throws \Throwable
     */
    public function index(Section $section, Article $article, string $article_code, string $category = ''): string
    {
        $section->checkPath($category);
        $current_article = $article->getArticle($article_code);
        $this->meta_tags->forArticle($current_article)->set();
        return $this->render->render(
            'news::public/article',
            [
                'article' => $current_article,
                'current_section' => $section->getLastSection(),
            ]
        );
    }
}

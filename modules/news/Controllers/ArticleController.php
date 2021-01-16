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
use News\Article;
use News\MetaTagsManager;
use News\Section;

class ArticleController extends BaseController
{
    protected $module_name = 'news';

    /** @var array */
    protected $config;

    /** @var MetaTagsManager */
    protected $meta_tags;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->nav_chain->add(__('News'), '/news/');
        $this->meta_tags = new MetaTagsManager();
    }

    /**
     * Article page
     *
     * @param Section $section
     * @param Article $article
     * @param string $article_code
     * @param string $category
     * @return string
     */
    public function index(Section $section, Article $article, string $article_code, string $category = ''): string
    {
        $section->checkPath($category);
        $current_article = $article->getArticle($article_code);
        $this->render->addData($this->meta_tags->setForArticle($current_article)->toArray());
        return $this->render->render(
            'news::public/article',
            [
                'article'         => $current_article,
                'current_section' => $section->getLastSection(),
            ]
        );
    }
}

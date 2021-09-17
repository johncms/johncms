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
use Throwable;

class SectionController extends BaseController
{
    protected string $module_name = 'johncms/news';
    protected array $config;
    protected NewsMetaManager $newsMetaManager;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->navChain->add(__('News'), route('news.section'));
        $this->newsMetaManager = new NewsMetaManager();
    }

    /**
     * List of articles and sections
     *
     * @param Article $article
     * @param Section $section
     * @param string $category
     * @return string
     * @throws Throwable
     */
    public function index(Article $article, Section $section, string $category = ''): string
    {
        $section->checkPath($category);
        $current_section = $section->getLastSection();
        $this->newsMetaManager->forSection($current_section)->set();

        return $this->render->render(
            'news::public/index',
            [
                'current_section' => $current_section,
                'sections'        => $section->getSections($current_section->id ?? 0),
                'articles'        => $article->getArticles($section->getCachedSubsections($current_section)),
            ]
        );
    }
}

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

class SectionController extends BaseController
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
     * List of articles and sections
     *
     * @param Article $article
     * @param Section $section
     * @param string $category
     * @return string
     */
    public function index(Article $article, Section $section, string $category = ''): string
    {
        $section->checkPath($category);
        $current_section = $section->getLastSection();
        $this->render->addData($this->meta_tags->setForSection($current_section)->toArray());
        return $this->render->render(
            'news::public/index',
            [
                'sections'        => $section->getSections($current_section->id ?? 0),
                'articles'        => $article->getArticles($section->getCachedSubsections($current_section)),
                'current_section' => $current_section,
            ]
        );
    }
}

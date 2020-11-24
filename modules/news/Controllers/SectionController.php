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
use News\Models\NewsArticle;
use News\Models\NewsSection;
use News\Utils\Helpers;
use News\Utils\Subsections;

class SectionController extends BaseController
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
     * List of articles and sections
     *
     * @param string $category
     * @return string
     */
    public function index(string $category = ''): string
    {
        $current_section = null;
        $page_title = __('News');
        $title = __('News');

        if (! empty($category)) {
            $path = Helpers::checkPath($category);
            if (! empty($path)) {
                foreach ($path as $item) {
                    /** @var $item NewsSection */
                    $this->nav_chain->add($item->name, $item->url);
                }
                /** @var NewsSection $current_section */
                $current_section = $path[array_key_last($path)];
                $title = $current_section->meta_title;
                $page_title = $current_section->name;
                $keywords = $current_section->meta_keywords;
                $description = $current_section->meta_description;
            }
        }

        if ($current_section !== null) {
            $sections = (new NewsSection())->where('parent', $current_section->id)->get();

            // Get all articles in the current section with subsections
            /** @var Subsections $subsections */
            $subsections = di(Subsections::class);
            $ids = $subsections->getIds($current_section);
            $ids[] = $current_section->id;
            $articles = (new NewsArticle())
                ->active()
                ->withCount('comments')
                ->orderByDesc('id')
                ->whereIn('section_id', $ids)
                ->paginate();
        } else {
            $sections = (new NewsSection())->where('parent', 0)->get();
            $articles = (new NewsArticle())
                ->active()
                ->withCount('comments')
                ->orderByDesc('id')
                ->paginate();
            $title = $this->settings['title'] ?? __('News');
            $page_title = $this->config['title'] ?? __('News');
            $keywords = $this->config['meta_keywords'];
            $description = $this->config['meta_description'];
        }

        $this->render->addData(
            [
                'title'       => $title,
                'page_title'  => $page_title,
                'keywords'    => $keywords ?? '',
                'description' => $description ?? '',
            ]
        );

        return $this->render->render(
            'news::public/index',
            [
                'sections'        => $sections,
                'articles'        => $articles,
                'current_section' => $current_section,
            ]
        );
    }
}

<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace News;

use News\Models\NewsArticle;
use News\Models\NewsSection;

class MetaTagsManager
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $page_title;

    /** @var string */
    protected $keywords;

    /** @var string */
    protected $description;

    /** @var array */
    protected $config;

    public function __construct()
    {
        $this->config = di('config')['news'] ?? [];
        $this->title = $this->config['title'] ?? __('News');
        $this->page_title = $this->config['title'] ?? __('News');
        $this->keywords = $this->config['meta_keywords'] ?? '';
        $this->description = $this->config['meta_description'] ?? '';
    }

    public function setForSection(?NewsSection $section): MetaTagsManager
    {
        if ($section === null) {
            return $this;
        }

        $this->title = $section->name;
        $this->page_title = $section->name;
        if (! empty($this->config['section_title'])) {
            $this->title = str_replace('#section_name#', $section->name, $this->config['section_title']);
        }

        if (empty($section->keywords)) {
            $this->keywords = str_replace('#section_name#', $section->name, $this->config['section_meta_keywords']);
        } else {
            $this->keywords = $section->keywords;
        }

        if (empty($section->description)) {
            $this->description = str_replace('#section_name#', $section->name, $this->config['section_meta_description']);
        } else {
            $this->description = $section->description;
        }

        return $this;
    }

    public function setForArticle(NewsArticle $article): MetaTagsManager
    {
        return $this;
    }

    public function toArray(): array
    {
        return [
            'title'       => $this->title,
            'page_title'  => $this->page_title,
            'keywords'    => $this->keywords,
            'description' => $this->description,
        ];
    }
}

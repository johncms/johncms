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
        $this->title = $this->config['title'] ? $this->config['title'] : __('News');
        $this->page_title = $this->config['title'] ? $this->config['title'] : __('News');
        $this->keywords = $this->config['meta_keywords'] ?? '';
        $this->description = $this->config['meta_description'] ?? '';
    }

    public function setForSection(?NewsSection $section): MetaTagsManager
    {
        if ($section === null) {
            return $this;
        }

        $this->title = $section->getRawOriginal('name');
        $this->page_title = $section->getRawOriginal('name');
        if (! empty($this->config['section_title'])) {
            $this->title = str_replace('#section_name#', $section->getRawOriginal('name'), $this->config['section_title']);
        }

        if (empty($section->keywords)) {
            $this->keywords = str_replace('#section_name#', $section->getRawOriginal('name'), $this->config['section_meta_keywords']);
        } else {
            $this->keywords = $section->getRawOriginal('keywords');
        }

        if (empty($section->description)) {
            $this->description = str_replace('#section_name#', $section->getRawOriginal('name'), $this->config['section_meta_description']);
        } else {
            $this->description = $section->getRawOriginal('description');
        }

        return $this;
    }

    public function setForArticle(?NewsArticle $article): MetaTagsManager
    {
        if ($article === null) {
            return $this;
        }

        $this->page_title = $article->getRawOriginal('name');
        if (empty($article->page_title)) {
            $this->title = str_replace('#article_name#', $article->getRawOriginal('name'), $this->config['article_title']);
        } else {
            $this->title = $article->getRawOriginal('page_title');
        }

        if (empty($article->keywords)) {
            $this->keywords = str_replace('#article_name#', $article->getRawOriginal('name'), $this->config['article_meta_keywords']);
        } else {
            $this->keywords = $article->getRawOriginal('keywords');
        }

        if (empty($article->description)) {
            $this->description = str_replace('#article_name#', $article->getRawOriginal('name'), $this->config['article_meta_description']);
        } else {
            $this->description = $article->getRawOriginal('description');
        }

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

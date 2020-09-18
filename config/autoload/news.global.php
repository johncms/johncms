<?php

use News\Utils\SectionPathCache;
use News\Utils\Subsections;

return [
    'news'         =>
        [
            'title'            => null,
            'meta_keywords'    => 'keywords',
            'meta_description' => 'descriptions',

            'section_title'            => '#section_name#',
            'section_meta_keywords'    => '#section_name#',
            'section_meta_description' => '#section_name#',

            'article_title'            => '#article_name#',
            'article_meta_keywords'    => '#article_name#',
            'article_meta_description' => '#article_name#',
        ],
    'dependencies' => [
        'factories' => [
            SectionPathCache::class => SectionPathCache::class,
            Subsections::class      => Subsections::class,
        ],
    ],
];

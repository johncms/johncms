<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Enums;

/**
 * What the list of the news in the panel shows: the articles, or the sections.
 */
enum NewsListTab: string
{
    case ARTICLES = 'articles';
    case SECTIONS = 'sections';
}

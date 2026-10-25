<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Enums;

use Johncms\Modules\Admin\Domain\Enums\SortDirection;

/**
 * What the list of the news can be ordered by. The query maps every case to its column, so a
 * value from the address never reaches the SQL itself.
 */
enum NewsListSort: string
{
    case ID = 'id';
    case NAME = 'name';
    case CREATED = 'created';
    case UPDATED = 'updated';
    case VIEWS = 'views';
    case ARTICLES = 'articles';

    /**
     * The order a tab takes when nothing is asked for: the newest articles, the sections by name.
     */
    public static function defaultFor(NewsListTab $tab): self
    {
        return $tab === NewsListTab::ARTICLES ? self::ID : self::NAME;
    }

    /**
     * Whether the tab has the column: the views belong to the articles, the number of articles to
     * the sections.
     */
    public function isOf(NewsListTab $tab): bool
    {
        return match ($this) {
            self::VIEWS    => $tab === NewsListTab::ARTICLES,
            self::ARTICLES => $tab === NewsListTab::SECTIONS,
            default        => true,
        };
    }

    /**
     * The direction the list takes when the column is first clicked: names from A, the rest from
     * the most.
     */
    public function defaultDirection(): SortDirection
    {
        return $this === self::NAME ? SortDirection::ASC : SortDirection::DESC;
    }
}

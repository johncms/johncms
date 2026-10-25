<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Services;

use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use Johncms\Modules\News\Domain\Enums\NewsListSort;
use Johncms\Modules\News\Domain\Enums\NewsListTab;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;

/**
 * The filter of the list of the news and the query string of its address, both ways.
 *
 *     tab        sections — the sections; the articles by default
 *     q          a part of a name or of a code, a number
 *     section    the number of a section, 0 for the root
 *     status     published | draft | scheduled | expired, of the articles
 *     author     the number of an account, of the articles
 *     sort, dir  the column and the direction
 */
final readonly class NewsListFilterMapper
{
    private const SEARCH_LENGTH = 100;

    /**
     * @param array<string, mixed> $query Anything that is not one of the values above reads as
     *                                    its default.
     */
    public function fromQuery(array $query): NewsListFilter
    {
        $tab = NewsListTab::tryFrom($this->string($query, 'tab')) ?? NewsListTab::ARTICLES;
        $articles = $tab === NewsListTab::ARTICLES;

        return new NewsListFilter(
            tab: $tab,
            search: mb_substr(trim($this->string($query, 'q')), 0, self::SEARCH_LENGTH),
            section: $this->number($query, 'section'),
            status: $articles ? ArticleStatus::tryFrom($this->string($query, 'status')) : null,
            author: $articles ? $this->number($query, 'author') : null,
            sort: NewsListSort::tryFrom($this->string($query, 'sort')),
            direction: SortDirection::tryFrom($this->string($query, 'dir')),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(NewsListFilter $filter): array
    {
        $query = [];

        if ($filter->tab !== NewsListTab::ARTICLES) {
            $query['tab'] = $filter->tab->value;
        }

        if ($filter->search !== '') {
            $query['q'] = $filter->search;
        }

        if ($filter->section !== null) {
            $query['section'] = (string) $filter->section;
        }

        if ($filter->status !== null) {
            $query['status'] = $filter->status->value;
        }

        if ($filter->author !== null) {
            $query['author'] = (string) $filter->author;
        }

        if ($filter->sort !== NewsListSort::defaultFor($filter->tab)) {
            $query['sort'] = $filter->sort->value;
        }

        if ($filter->direction !== $filter->sort->defaultDirection()) {
            $query['dir'] = $filter->direction->value;
        }

        return $query;
    }

    public function url(string $path, NewsListFilter $filter): string
    {
        $query = http_build_query($this->toQuery($filter));

        return $path . ($query !== '' ? '?' . $query : '');
    }

    /**
     * @param array<string, mixed> $query
     */
    private function string(array $query, string $key): string
    {
        $value = $query[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $query
     */
    private function number(array $query, string $key): ?int
    {
        $value = $this->string($query, $key);

        return ctype_digit($value) ? (int) $value : null;
    }
}

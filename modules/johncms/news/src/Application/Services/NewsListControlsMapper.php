<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Application\Services\ListFilterControl;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use Johncms\Modules\News\Domain\Enums\NewsListSort;
use Johncms\Modules\News\Domain\Enums\NewsListTab;
use Johncms\Modules\News\Domain\Services\SectionTree;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\Users\User;

/**
 * What the list of the news is steered with, as news::admin/index draws it: the tabs, the
 * dropdowns of the filters, the chips of what is applied, the links of the sortable columns and
 * the hidden fields that keep the filter while a search is typed.
 *
 * Every link leads to the first page: a page number means nothing once the list has changed.
 */
final readonly class NewsListControlsMapper
{
    public const PATH = '/admin/news';

    public function __construct(
        private NewsListFilterMapper $filterMapper,
        private ListFilterControl $control,
    ) {
    }

    /**
     * @param array<string, int>    $tabCounts What each tab holds, keyed by the value of NewsListTab.
     * @param Collection<int, User> $authors
     * @return array<string, mixed>
     */
    public function map(NewsListFilter $filter, array $tabCounts, SectionTree $tree, Collection $authors): array
    {
        $filters = [$this->sectionFilter($filter, $tree)];
        if ($filter->tab === NewsListTab::ARTICLES) {
            $filters[] = $this->statusFilter($filter);
            $filters[] = $this->authorFilter($filter, $authors);
        }

        $chips = [];
        if ($filter->search !== '') {
            $chips[] = ['label' => __('Search'), 'value' => $filter->search, 'url' => $this->url($filter->withSearch(''))];
        }

        foreach ($filters as $control) {
            if ($control['chip'] !== null) {
                $chips[] = $control['chip'];
            }
        }

        return [
            'action'    => self::PATH,
            'list_url'  => $this->url($filter),
            'tabs'      => $this->tabs($filter, $tabCounts),
            'filters'   => $filters,
            'chips'     => $chips,
            'clear_url' => $chips === [] ? '' : $this->url(new NewsListFilter(tab: $filter->tab, sort: $filter->sort, direction: $filter->direction)),
            'sort'      => $this->sortLinks($filter),
            // The search field submits these along with what is typed, so the rest of the filter
            // stays as it is; the field itself carries the search.
            'state'     => $this->filterMapper->toQuery($filter->withSearch('')),
        ];
    }

    public function url(NewsListFilter $filter): string
    {
        return $this->filterMapper->url(self::PATH, $filter);
    }

    /**
     * Where an action taken on the list returns to: the page of the list it was taken on, or the
     * list itself. An address that is not one of the list is not followed, so a form cannot be
     * made to lead somewhere else.
     */
    public function returnUrl(string $back): string
    {
        return $back === self::PATH || str_starts_with($back, self::PATH . '?') ? $back : self::PATH;
    }

    /**
     * @param array<string, int> $counts
     * @return list<array<string, mixed>>
     */
    private function tabs(NewsListFilter $filter, array $counts): array
    {
        $titles = [
            NewsListTab::ARTICLES->value => __('Articles'),
            NewsListTab::SECTIONS->value => __('Sections'),
        ];

        $tabs = [];
        foreach (NewsListTab::cases() as $tab) {
            $tabs[] = [
                'title'  => $titles[$tab->value],
                // The section is kept: it narrows both tabs alike. The rest is of the articles.
                'url'    => $this->url(new NewsListFilter(tab: $tab, section: $filter->section)),
                'active' => $filter->tab === $tab,
                'count'  => $counts[$tab->value] ?? 0,
                'tone'   => '',
            ];
        }

        return $tabs;
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionFilter(NewsListFilter $filter, SectionTree $tree): array
    {
        $options = [
            ['title' => __('All sections'), 'url' => $this->url($filter->withSection(null)), 'active' => $filter->section === null],
            ['title' => __('Root'), 'url' => $this->url($filter->withSection(NewsListFilter::ROOT)), 'active' => $filter->section === NewsListFilter::ROOT],
        ];

        foreach ($tree->ordered() as $id) {
            $options[] = [
                'title'  => implode(' › ', $tree->pathOf($id)),
                'url'    => $this->url($filter->withSection($id)),
                'active' => $filter->section === $id,
            ];
        }

        return $this->control->build('section', __('Section'), $options, $this->url($filter->withSection(null)));
    }

    /**
     * @return array<string, mixed>
     */
    private function statusFilter(NewsListFilter $filter): array
    {
        $options = [['title' => __('Any'), 'url' => $this->url($filter->withStatus(null)), 'active' => $filter->status === null]];

        foreach (ArticleStatus::cases() as $status) {
            $options[] = ['title' => self::statusTitle($status), 'url' => $this->url($filter->withStatus($status)), 'active' => $filter->status === $status];
        }

        return $this->control->build('status', __('Status'), $options, $this->url($filter->withStatus(null)));
    }

    /**
     * @param Collection<int, User> $authors
     * @return array<string, mixed>
     */
    private function authorFilter(NewsListFilter $filter, Collection $authors): array
    {
        $options = [['title' => __('Anybody'), 'url' => $this->url($filter->withAuthor(null)), 'active' => $filter->author === null]];

        foreach ($authors as $author) {
            $options[] = ['title' => $author->name, 'url' => $this->url($filter->withAuthor($author->id)), 'active' => $filter->author === $author->id];
        }

        return $this->control->build('author', __('Author'), $options, $this->url($filter->withAuthor(null)));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sortLinks(NewsListFilter $filter): array
    {
        $links = [];

        foreach (NewsListSort::cases() as $sort) {
            if (! $sort->isOf($filter->tab)) {
                continue;
            }

            $active = $filter->sort === $sort;
            $direction = $active ? $filter->direction->reverse() : $sort->defaultDirection();

            $links[$sort->value] = [
                'url'       => $this->url($filter->withSort($sort, $direction)),
                'active'    => $active,
                'direction' => $active ? $filter->direction->value : '',
            ];
        }

        return $links;
    }

    public static function statusTitle(ArticleStatus $status): string
    {
        return match ($status) {
            ArticleStatus::PUBLISHED => __('Published'),
            ArticleStatus::DRAFT     => __('Draft'),
            ArticleStatus::SCHEDULED => __('Scheduled'),
            ArticleStatus::EXPIRED   => __('Expired'),
        };
    }
}

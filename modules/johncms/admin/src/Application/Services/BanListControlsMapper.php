<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Modules\Admin\Domain\Enums\BanListStatus;
use Johncms\Modules\Admin\Domain\ValueObjects\BanListFilter;
use Johncms\Users\Bans\BanKind;

/**
 * What the ban panel is steered with, as admin::bans draws it: the tabs, the dropdown of the
 * kind, the chips of what is applied, the links of the sortable columns and the hidden fields
 * that keep the filter while a search is typed.
 *
 * Every link leads to the first page: a page number means nothing once the list has changed.
 */
final readonly class BanListControlsMapper
{
    public const PATH = '/admin/bans';

    public function __construct(
        private BanListFilterMapper $filterMapper,
        private ListFilterControl $control,
    ) {
    }

    /**
     * @param array<string, int> $tabCounts Accounts per tab, keyed by the value of BanListStatus.
     * @return array<string, mixed>
     */
    public function map(BanListFilter $filter, array $tabCounts): array
    {
        $filters = [$this->kindFilter($filter)];
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
            'clear_url' => $chips === [] ? '' : $this->url(new BanListFilter(status: $filter->status, sort: $filter->sort)),
            'sort'      => $this->sortLinks($filter),
            // The search field submits these along with what is typed, so the rest of the filter
            // stays as it is; the field itself carries the search.
            'state'     => $this->filterMapper->toQuery($filter->withSearch('')),
        ];
    }

    public function url(BanListFilter $filter): string
    {
        return $this->filterMapper->url(self::PATH, $filter);
    }

    /**
     * @param array<string, int> $counts
     * @return list<array<string, mixed>>
     */
    private function tabs(BanListFilter $filter, array $counts): array
    {
        $titles = [
            BanListStatus::ACTIVE->value => __('Banned now'),
            BanListStatus::ALL->value    => __('Every offender'),
        ];

        $tabs = [];
        foreach (BanListStatus::cases() as $status) {
            $tabs[] = [
                'title'  => $titles[$status->value],
                'url'    => $this->url($filter->withStatus($status)),
                'active' => $filter->status === $status,
                'count'  => $counts[$status->value] ?? 0,
                'tone'   => $status === BanListStatus::ACTIVE ? 'danger' : '',
            ];
        }

        return $tabs;
    }

    /**
     * @return array<string, mixed>
     */
    private function kindFilter(BanListFilter $filter): array
    {
        $options = [['title' => __('Any'), 'url' => $this->url($filter->withKind(null)), 'active' => $filter->kind === null]];

        foreach (BanKind::cases() as $kind) {
            $options[] = ['title' => $kind->title(), 'url' => $this->url($filter->withKind($kind)), 'active' => $filter->kind === $kind];
        }

        return $this->control->build('kind', __('Kind'), $options, $this->url($filter->withKind(null)));
    }

    /**
     * The list is always ordered from the most down, so a column is either the order or a link
     * to it.
     *
     * @return array<string, array<string, mixed>>
     */
    private function sortLinks(BanListFilter $filter): array
    {
        $links = [];

        foreach (BanListSort::cases() as $sort) {
            $active = $filter->sort === $sort;
            $links[$sort->value] = [
                'url'       => $this->url($filter->withSort($sort)),
                'active'    => $active,
                'direction' => $active ? 'desc' : '',
            ];
        }

        return $links;
    }
}

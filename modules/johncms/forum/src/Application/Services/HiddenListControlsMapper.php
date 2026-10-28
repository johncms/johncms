<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Admin\Application\Services\ListFilterControl;
use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;
use Johncms\Modules\Forum\Domain\ValueObjects\HiddenListFilter;

/**
 * What the list of the hidden content of the forum is steered with, as forum::admin/hidden draws
 * it: the tabs, the dropdown of who hid it, the chips of what is applied and the hidden fields that
 * keep the filter while a search is typed.
 *
 * Every link leads to the first page: a page number means nothing once the list has changed.
 */
final readonly class HiddenListControlsMapper
{
    public function __construct(
        private HiddenListFilterMapper $filterMapper,
        private ListFilterControl $control,
    ) {
    }

    /**
     * @param array<string, int>                                 $totals Keyed by the value of HiddenListTab.
     * @param list<string>                                       $hiders Who hid something in the tab.
     * @param array{author: string, topic: string, section: string} $names  What the numbers in the filter stand for.
     * @return array<string, mixed>
     */
    public function map(HiddenListFilter $filter, array $totals, array $hiders, array $names): array
    {
        $chips = $this->chips($filter, $names);

        return [
            'action'    => HiddenListFilterMapper::PATH,
            'list_url'  => $this->filterMapper->url($filter),
            'tabs'      => $this->tabs($filter, $totals),
            'filters'   => [$this->hiddenByFilter($filter, $hiders)],
            'chips'     => $chips,
            'clear_url' => $chips === [] ? '' : $this->filterMapper->url(new HiddenListFilter($filter->tab)),
            // The search field submits these along with what is typed, so the rest of the filter
            // stays as it is; the field itself carries the search.
            'state'     => $this->filterMapper->toQuery($filter->withSearch('')),
        ];
    }

    /**
     * What the filter is narrowed by, one chip each, a link without it on every chip.
     *
     * @param array{author: string, topic: string, section: string} $names What the numbers in the filter stand for.
     * @return list<array{label: string, value: string, url: string}>
     */
    public function chips(HiddenListFilter $filter, array $names): array
    {
        $chips = [];
        if ($filter->search !== '') {
            $chips[] = $this->chip(__('Search'), $filter->search, $filter->withSearch(''));
        }
        if ($filter->author !== null) {
            $chips[] = $this->chip(__('Author'), $names['author'], $filter->withAuthor(null));
        }
        if ($filter->topic !== null) {
            $chips[] = $this->chip(__('Topic'), $names['topic'], $filter->withTopic(null));
        }
        if ($filter->section !== null) {
            $chips[] = $this->chip(__('Section'), $names['section'], $filter->withSection(null));
        }
        if ($filter->hiddenBy !== null) {
            $chips[] = $this->chip(__('Hidden by'), $filter->hiddenBy, $filter->withHiddenBy(null));
        }

        return $chips;
    }

    /**
     * @param array<string, int> $totals
     * @return list<array<string, mixed>>
     */
    private function tabs(HiddenListFilter $filter, array $totals): array
    {
        $titles = [
            HiddenListTab::POSTS->value  => __('Posts'),
            HiddenListTab::TOPICS->value => __('Topics'),
        ];

        $tabs = [];
        foreach (HiddenListTab::cases() as $tab) {
            $tabs[] = [
                'title'  => $titles[$tab->value],
                // The author and who hid it mean the same on both tabs; the rest is of one tab.
                'url'    => $this->filterMapper->url(new HiddenListFilter($tab, author: $filter->author, hiddenBy: $filter->hiddenBy)),
                'active' => $filter->tab === $tab,
                'count'  => $totals[$tab->value] ?? 0,
                'tone'   => '',
            ];
        }

        return $tabs;
    }

    /**
     * @param list<string> $hiders
     * @return array<string, mixed>
     */
    private function hiddenByFilter(HiddenListFilter $filter, array $hiders): array
    {
        // A name in the address nobody hid anything under is still offered, so the filter shows
        // what it is narrowed to.
        if ($filter->hiddenBy !== null && ! in_array($filter->hiddenBy, $hiders, true)) {
            $hiders[] = $filter->hiddenBy;
        }

        $options = [['title' => __('Anybody'), 'url' => $this->filterMapper->url($filter->withHiddenBy(null)), 'active' => $filter->hiddenBy === null]];
        foreach ($hiders as $name) {
            $options[] = ['title' => $name, 'url' => $this->filterMapper->url($filter->withHiddenBy($name)), 'active' => $filter->hiddenBy === $name];
        }

        return $this->control->build('by', __('Hidden by'), $options, $this->filterMapper->url($filter->withHiddenBy(null)));
    }

    /**
     * @return array{label: string, value: string, url: string}
     */
    private function chip(string $label, string $value, HiddenListFilter $without): array
    {
        return ['label' => $label, 'value' => $value, 'url' => $this->filterMapper->url($without)];
    }
}

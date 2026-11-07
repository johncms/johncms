<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Admin\Application\Services\ListFilterControl;
use Johncms\Modules\Collections\Domain\Enums\ItemStatus;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;

/**
 * What the list of the items of a collection is steered with, as collections::admin/items draws
 * it: the dropdowns of the filters, the chips of what is applied and the hidden fields that keep
 * the filter while a search is typed.
 *
 * Every link leads to the first page: a page number means nothing once the list has changed.
 */
final readonly class ItemListControlsMapper
{
    /** What reads best newest or largest first. */
    private const array DESCENDING_FIRST = ['created_at', 'updated_at', 'view_count', 'active_from'];

    public function __construct(
        private ItemListFilterMapper $filterMapper,
        private ListFilterControl $control,
        private CollectionListSettings $listing = new CollectionListSettings(),
    ) {
    }

    /**
     * @param string $path The list of the collection, without a query.
     * @return array<string, mixed>
     */
    public function map(string $path, ItemListFilter $filter, SectionTree $tree, bool $hasSections): array
    {
        $filters = [];
        if ($hasSections) {
            $filters[] = $this->sectionFilter($path, $filter, $tree);
        }
        $filters[] = $this->statusFilter($path, $filter);
        // Last, and never a chip: an order does not narrow the list.
        $filters[] = $this->sortControl($path, $filter);

        $chips = [];
        if ($filter->search !== '') {
            $chips[] = ['label' => __('Search'), 'value' => $filter->search, 'url' => $this->filterMapper->url($path, $filter->withSearch(''))];
        }

        foreach ($filters as $control) {
            if ($control['chip'] !== null) {
                $chips[] = $control['chip'];
            }
        }

        foreach (
            [
            'author' => [d__('collections', 'Author ID'), $filter->author],
            'created_from' => [d__('collections', 'Created from'), $filter->createdFrom],
            'created_to' => [d__('collections', 'Created to'), $filter->createdTo],
            'min_views' => [d__('collections', 'Minimum views'), $filter->minViews],
            ] as $key => [$label, $value]
        ) {
            if ($value !== null) {
                $query = $this->filterMapper->toQuery($filter);
                unset($query[$key]);
                $chips[] = ['label' => $label, 'value' => (string) $value, 'url' => $path . ($query !== [] ? '?' . http_build_query($query) : '')];
            }
        }

        return [
            'action'    => $path,
            'search' => $filter->search,
            'filters'   => $filters,
            'sort_links' => $this->sortLinks($path, $filter),
            'chips'     => $chips,
            'clear_url' => $chips === [] ? '' : $path,
            // The search field submits these along with what is typed, so the rest of the filter
            // stays as it is; the field itself carries the search.
            'state'     => $this->filterMapper->toQuery($filter->withSearch('')),
        ];
    }

    public static function statusTitle(ItemStatus $status): string
    {
        return match ($status) {
            ItemStatus::PUBLISHED => __('Published'),
            ItemStatus::HIDDEN    => __('Hidden'),
            ItemStatus::SCHEDULED => __('Scheduled'),
            ItemStatus::EXPIRED   => __('Expired'),
        };
    }

    /**
     * The order of the list as a dropdown among the filters: the fields first, then the direction.
     *
     * @return array<string, mixed>
     */
    private function sortControl(string $path, ItemListFilter $filter): array
    {
        $sorts = $this->listing->sorts();
        $options = [];
        foreach ($sorts as $key => $title) {
            $direction = $key === $filter->sortBy ? $filter->direction : $this->firstDirection($key);
            $options[] = ['title' => $title, 'url' => $this->filterMapper->url($path, $filter->withOrder($key, $direction)), 'active' => $key === $filter->sortBy];
        }
        foreach (['asc' => __('Ascending'), 'desc' => __('Descending')] as $direction => $title) {
            $options[] = [
                'title'   => $title,
                'url'     => $this->filterMapper->url($path, $filter->withOrder($filter->sortBy, $direction)),
                'active'  => $filter->direction === $direction,
                'divider' => $direction === 'asc',
            ];
        }

        return [
            'name'    => 'order',
            'label'   => __('Sorting'),
            'value'   => ($sorts[$filter->sortBy] ?? $filter->sortBy) . ' ' . ($filter->direction === 'asc' ? '↑' : '↓'),
            'applied' => false,
            'options' => $options,
            'chip'    => null,
        ];
    }

    /**
     * The headers of the columns the list can be ordered by: a click orders by the column, and a
     * click on the current one turns the direction over.
     *
     * @return array<string, array{url: string, direction: ?string}>
     */
    private function sortLinks(string $path, ItemListFilter $filter): array
    {
        $links = [];
        foreach (array_keys($this->listing->sorts()) as $key) {
            $current = $key === $filter->sortBy;
            $next = $current ? ($filter->direction === 'asc' ? 'desc' : 'asc') : $this->firstDirection($key);
            $links[$key] = ['url' => $this->filterMapper->url($path, $filter->withOrder($key, $next)), 'direction' => $current ? $filter->direction : null];
        }

        return $links;
    }

    private function firstDirection(string $sort): string
    {
        return in_array($sort, self::DESCENDING_FIRST, true) ? 'desc' : 'asc';
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionFilter(string $path, ItemListFilter $filter, SectionTree $tree): array
    {
        $url = fn (?int $section): string => $this->filterMapper->url($path, $filter->withSection($section));
        $options = [
            ['title' => __('All sections'), 'url' => $url(null), 'active' => $filter->section === null],
            ['title' => __('Without a section'), 'url' => $url(ItemListFilter::WITHOUT_SECTION), 'active' => $filter->section === ItemListFilter::WITHOUT_SECTION],
        ];

        foreach ($tree->ordered() as $id) {
            $options[] = [
                'title'  => implode(' › ', $tree->pathOf($id)),
                'url'    => $url($id),
                'active' => $filter->section === $id,
            ];
        }

        return $this->control->build('section', __('Section'), $options, $url(null));
    }

    /**
     * @return array<string, mixed>
     */
    private function statusFilter(string $path, ItemListFilter $filter): array
    {
        $url = fn (?ItemStatus $status): string => $this->filterMapper->url($path, $filter->withStatus($status));
        $options = [['title' => __('Any'), 'url' => $url(null), 'active' => $filter->status === null]];

        foreach (ItemStatus::cases() as $status) {
            $options[] = ['title' => self::statusTitle($status), 'url' => $url($status), 'active' => $filter->status === $status];
        }

        return $this->control->build('status', __('Status'), $options, $url(null));
    }
}

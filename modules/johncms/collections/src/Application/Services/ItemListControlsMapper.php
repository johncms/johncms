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
    public function __construct(
        private ItemListFilterMapper $filterMapper,
        private ListFilterControl $control,
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

        $chips = [];
        if ($filter->search !== '') {
            $chips[] = ['label' => __('Search'), 'value' => $filter->search, 'url' => $this->filterMapper->url($path, $filter->withSearch(''))];
        }

        foreach ($filters as $control) {
            if ($control['chip'] !== null) {
                $chips[] = $control['chip'];
            }
        }

        return [
            'action'    => $path,
            'filters'   => $filters,
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

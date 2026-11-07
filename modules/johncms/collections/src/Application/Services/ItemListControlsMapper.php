<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use DateTimeImmutable;
use Johncms\Modules\Admin\Application\Services\ListFilterControl;
use Johncms\Modules\Collections\Domain\Enums\ItemStatus;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;
use Johncms\Users\Repository\UserRepositoryInterface;

/**
 * What the list of the items of a collection is steered with, as collections::admin/items draws
 * it: the dropdowns of the filters, the chips of what is applied and the hidden fields that keep
 * the filter while a search is typed.
 *
 * Every link leads to the first page: a page number means nothing once the list has changed.
 */
final readonly class ItemListControlsMapper
{
    private const int AUTHORS = 100;
    private const array VIEWS = [10, 100, 1000];

    /** What reads best newest or largest first. */
    private const array DESCENDING_FIRST = ['created_at', 'updated_at', 'view_count', 'active_from'];

    public function __construct(
        private ItemListFilterMapper $filterMapper,
        private ListFilterControl $control,
        private CollectionListSettings $listing = new CollectionListSettings(),
        private ?ContentCollectionItemRepositoryInterface $items = null,
        private ?UserRepositoryInterface $users = null,
    ) {
    }

    /**
     * @param string       $path    The list of the collection, without a query.
     * @param list<string> $enabled The extra filters the collection offers: author, created_at, views.
     * @return array<string, mixed>
     */
    public function map(string $path, ItemListFilter $filter, SectionTree $tree, bool $hasSections, array $enabled = [], ?int $collectionId = null, ?DateTimeImmutable $today = null): array
    {
        $filters = [];
        if ($hasSections) {
            $filters[] = $this->sectionFilter($path, $filter, $tree);
        }
        $filters[] = $this->statusFilter($path, $filter);
        if (in_array('author', $enabled, true) && $collectionId !== null) {
            $filters[] = $this->authorFilter($path, $filter, $collectionId);
        }
        if (in_array('created_at', $enabled, true)) {
            $filters[] = $this->createdFilter($path, $filter, $today ?? new DateTimeImmutable());
        }
        if (in_array('views', $enabled, true)) {
            $filters[] = $this->viewsFilter($path, $filter);
        }
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
     * Who created the items: the authors of the collection by name, searchable once there are many.
     *
     * @return array<string, mixed>
     */
    private function authorFilter(string $path, ItemListFilter $filter, int $collectionId): array
    {
        $ids = $this->items?->getAuthors($collectionId, self::AUTHORS) ?? [];
        if ($filter->author !== null && ! in_array($filter->author, $ids, true)) {
            $ids[] = $filter->author;
        }
        $names = [];
        foreach ($this->users?->findMany($ids) ?? [] as $user) {
            $names[$user->id] = $user->name;
        }
        $url = fn (?int $author): string => $this->filterMapper->url($path, $filter->withAuthor($author));
        $options = [['title' => __('Anyone'), 'url' => $url(null), 'active' => $filter->author === null]];
        foreach ($ids as $id) {
            $options[] = ['title' => $names[$id] ?? '#' . $id, 'url' => $url($id), 'active' => $filter->author === $id];
        }

        return $this->control->build('author', __('Created by'), $options, $url(null)) + ['searchable' => count($ids) > 8];
    }

    /**
     * When the items were created: periods back from today, and a range of one's own.
     *
     * @return array<string, mixed>
     */
    private function createdFilter(string $path, ItemListFilter $filter, DateTimeImmutable $today): array
    {
        $url = fn (?string $from, ?string $to = null): string => $this->filterMapper->url($path, $filter->withCreated($from, $to));
        $periods = [
            __('Today')        => $today->format('Y-m-d'),
            __('Last 7 days')  => $today->modify('-6 days')->format('Y-m-d'),
            __('Last 30 days') => $today->modify('-29 days')->format('Y-m-d'),
            __('This year')    => $today->format('Y') . '-01-01',
        ];
        $any = $filter->createdFrom === null && $filter->createdTo === null;
        $options = [['title' => __('Any date'), 'url' => $url(null), 'active' => $any]];
        $known = false;
        foreach ($periods as $title => $from) {
            $active = $filter->createdFrom === $from && $filter->createdTo === null;
            $known = $known || $active;
            $options[] = ['title' => $title, 'url' => $url($from), 'active' => $active];
        }
        if (! $any && ! $known) {
            $options[] = ['title' => $this->range($filter->createdFrom, $filter->createdTo), 'url' => $url($filter->createdFrom, $filter->createdTo), 'active' => true];
        }

        return $this->control->build('created', __('Created'), $options, $url(null)) + ['custom' => $this->customForm($path, $filter, [
            ['name' => 'created_from', 'label' => __('Created from'), 'type' => 'date', 'value' => $filter->createdFrom ?? ''],
            ['name' => 'created_to', 'label' => __('Created to'), 'type' => 'date', 'value' => $filter->createdTo ?? ''],
        ])];
    }

    /**
     * How often the items were read: a few thresholds, and a number of one's own.
     *
     * @return array<string, mixed>
     */
    private function viewsFilter(string $path, ItemListFilter $filter): array
    {
        $url = fn (?int $views): string => $this->filterMapper->url($path, $filter->withMinViews($views));
        $active = $filter->minViews !== null && $filter->minViews > 0 ? $filter->minViews : null;
        $options = [['title' => __('Any'), 'url' => $url(null), 'active' => $active === null]];
        foreach (array_unique([...self::VIEWS, ...($active !== null ? [$active] : [])]) as $views) {
            $options[] = ['title' => sprintf(__('From %s'), number_format($views, 0, '', ' ')), 'url' => $url($views), 'active' => $active === $views];
        }

        return $this->control->build('views', __('Views'), $options, $url(null)) + ['custom' => $this->customForm($path, $filter, [
            ['name' => 'min_views', 'label' => __('Minimum views'), 'type' => 'number', 'value' => $active ?? ''],
        ])];
    }

    /**
     * A form of its own values for a filter: the rest of the filter rides along as hidden fields.
     *
     * @param list<array{name: string, label: string, type: string, value: string|int}> $fields
     * @return array<string, mixed>
     */
    private function customForm(string $path, ItemListFilter $filter, array $fields): array
    {
        $hidden = $this->filterMapper->toQuery($filter);
        foreach ($fields as $field) {
            unset($hidden[$field['name']]);
        }

        return ['action' => $path, 'hidden' => $hidden, 'fields' => $fields, 'submit' => __('Apply')];
    }

    private function range(?string $from, ?string $to): string
    {
        $date = static fn (string $value): string => DateTimeImmutable::createFromFormat('!Y-m-d', $value)->format('d.m.Y');
        return match (true) {
            $from !== null && $to !== null => sprintf(__('%1$s – %2$s'), $date($from), $date($to)),
            $from !== null => sprintf(__('Since %s'), $date($from)),
            default => sprintf(__('Until %s'), $date((string) $to)),
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

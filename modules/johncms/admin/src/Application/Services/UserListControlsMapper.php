<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use BackedEnum;
use Johncms\Auth\Authorization\Role;
use Johncms\Modules\Admin\Application\DTO\UserListStatsDTO;
use Johncms\Modules\Admin\Domain\Enums\UserListActivity;
use Johncms\Modules\Admin\Domain\Enums\UserListRegistered;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Enums\UserListStatus;
use Johncms\Modules\Admin\Domain\ValueObjects\UserListFilter;

/**
 * What the list of users is steered with, as admin::users draws it: the tabs of the status, the
 * dropdowns of the filters, the chips of what is applied, the links of the sortable columns and
 * the hidden fields that keep the filter while a search is typed.
 *
 * Every link leads to the first page: a page number means nothing once the list has changed.
 */
final readonly class UserListControlsMapper
{
    public const PATH = '/admin/users';

    public function __construct(
        private UserListFilterMapper $filterMapper,
    ) {
    }

    /**
     * @param list<Role> $roles The roles the list can be narrowed to.
     * @return array<string, mixed>
     */
    public function map(UserListFilter $filter, UserListStatsDTO $stats, array $roles): array
    {
        $filters = [
            $this->roleFilter($filter, $roles),
            $this->enumFilter('activity', __('Activity'), $filter->activity, $this->activityOptions(), static fn (UserListActivity $value): UserListFilter => $filter->withActivity($value)),
            $this->enumFilter('registered', __('Registration'), $filter->registered, $this->registeredOptions(), static fn (UserListRegistered $value): UserListFilter => $filter->withRegistered($value)),
        ];

        $chips = [];

        if ($filter->search !== '') {
            $chips[] = ['label' => __('Search'), 'value' => $filter->search, 'url' => $this->url($filter->withSearch(''))];
        }

        foreach ($filters as $control) {
            if ($control['chip'] !== null) {
                $chips[] = $control['chip'];
            }
        }

        $cleared = new UserListFilter(status: $filter->status, sort: $filter->sort, direction: $filter->direction);

        return [
            'action'    => self::PATH,
            'status'    => $filter->status->value,
            'tabs'      => $this->tabs($filter, $stats),
            'filters'   => $filters,
            'chips'     => $chips,
            'clear_url' => $chips === [] ? '' : $this->url($cleared),
            'sort'      => $this->sortLinks($filter),
            // The search field submits these along with what is typed, so the rest of the filter
            // stays as it is; the field itself carries the search.
            'state'     => $this->filterMapper->toQuery($filter->withSearch('')),
        ];
    }

    public function url(UserListFilter $filter): string
    {
        return $this->filterMapper->url(self::PATH, $filter);
    }

    /**
     * Where the numbers above the list lead: each opens the list it counts.
     *
     * @return array<string, string>
     */
    public function statUrls(): array
    {
        return [
            'total'      => self::PATH,
            'online'     => $this->url(new UserListFilter(activity: UserListActivity::ONLINE)),
            'registered' => $this->url(new UserListFilter(registered: UserListRegistered::DAY)),
            'banned'     => $this->url(new UserListFilter(status: UserListStatus::BANNED)),
            'pending'    => $this->url(new UserListFilter(status: UserListStatus::PENDING)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tabs(UserListFilter $filter, UserListStatsDTO $stats): array
    {
        $tabs = [
            [UserListStatus::ALL, __('All'), 0],
            [UserListStatus::PENDING, __('Awaiting approval'), $stats->pending],
            [UserListStatus::BANNED, __('Banned'), $stats->banned],
        ];

        return array_map(
            fn (array $tab): array => [
                'title'  => $tab[1],
                'url'    => $this->url($filter->withStatus($tab[0])),
                'active' => $filter->status === $tab[0],
                'count'  => $tab[2],
                'tone'   => $tab[0] === UserListStatus::PENDING ? 'danger' : '',
            ],
            $tabs
        );
    }

    /**
     * @param list<Role> $roles
     * @return array<string, mixed>
     */
    private function roleFilter(UserListFilter $filter, array $roles): array
    {
        $options = [
            ['title' => __('Any'), 'url' => $this->url($filter->withRole(false, null)), 'active' => ! $filter->staff && $filter->roleId === null],
            ['title' => __('Staff'), 'url' => $this->url($filter->withRole(true, null)), 'active' => $filter->staff],
        ];

        foreach ($roles as $role) {
            $options[] = ['title' => $role->display_name, 'url' => $this->url($filter->withRole(false, $role->id)), 'active' => $filter->roleId === $role->id];
        }

        return $this->control('role', __('Role'), $options, $this->url($filter->withRole(false, null)));
    }

    /**
     * @template T of BackedEnum
     * @param T                           $current
     * @param array<string, string>       $titles  Value => title, the default first.
     * @param callable(T): UserListFilter $with
     * @return array<string, mixed>
     */
    private function enumFilter(string $name, string $label, BackedEnum $current, array $titles, callable $with): array
    {
        $options = [];
        $default = null;

        foreach ($titles as $value => $title) {
            $case = $current::from($value);
            $default ??= $case;
            $options[] = ['title' => $title, 'url' => $this->url($with($case)), 'active' => $case === $current];
        }

        return $this->control($name, $label, $options, $this->url($with($default)));
    }

    /**
     * @param list<array<string, mixed>> $options
     * @return array<string, mixed>
     */
    private function control(string $name, string $label, array $options, string $clearUrl): array
    {
        $active = array_values(array_filter($options, static fn (array $option): bool => $option['active']));
        $current = $active[0] ?? $options[0];
        // The first option is "any": choosing it is no filter at all.
        $applied = $current !== $options[0];

        return [
            'name'    => $name,
            'label'   => $label,
            'value'   => $current['title'],
            'applied' => $applied,
            'options' => $options,
            'chip'    => $applied ? ['label' => $label, 'value' => $current['title'], 'url' => $clearUrl] : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function activityOptions(): array
    {
        return [
            UserListActivity::ANY->value      => __('Any'),
            UserListActivity::ONLINE->value   => __('Online now'),
            UserListActivity::DAY->value      => __('In the last 24 hours'),
            UserListActivity::WEEK->value     => __('In the last 7 days'),
            UserListActivity::INACTIVE->value => __('Not seen for half a year'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function registeredOptions(): array
    {
        return [
            UserListRegistered::ANY->value   => __('Any time'),
            UserListRegistered::DAY->value   => __('In the last 24 hours'),
            UserListRegistered::WEEK->value  => __('In the last 7 days'),
            UserListRegistered::MONTH->value => __('In the last 30 days'),
            UserListRegistered::YEAR->value  => __('In the last year'),
        ];
    }

    /**
     * A sortable column links to the list ordered by it; the column the list is ordered by links
     * to the reverse order.
     *
     * @return array<string, array<string, mixed>>
     */
    private function sortLinks(UserListFilter $filter): array
    {
        $links = [];

        foreach (UserListSort::cases() as $sort) {
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
}

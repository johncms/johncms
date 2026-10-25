<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Events\AuthEventType;
use Johncms\Modules\Admin\Domain\Enums\AuthLogPeriod;
use Johncms\Modules\Admin\Domain\ValueObjects\AuthLogFilter;

/**
 * What the sign-in log is steered with, as admin::auth-log draws it: the dropdowns of the
 * filters, the chips of what is applied and the hidden fields that keep the filter while a
 * search is typed.
 *
 * Every link leads to the first page: a page number means nothing once the list has changed.
 */
final readonly class AuthLogControlsMapper
{
    public const PATH = '/admin/auth-log';

    public function __construct(
        private AuthLogFilterMapper $filterMapper,
        private ListFilterControl $control,
    ) {
    }

    /**
     * @param string|null $userName The account the log is narrowed to, for its chip.
     * @return array<string, mixed>
     */
    public function map(AuthLogFilter $filter, ?string $userName): array
    {
        $filters = [$this->eventFilter($filter), $this->periodFilter($filter)];
        $chips = [];

        if ($filter->userId !== null) {
            $chips[] = ['label' => __('User'), 'value' => $userName ?? '#' . $filter->userId, 'url' => $this->url($filter->withUser(null))];
        }

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
            'filters'   => $filters,
            'chips'     => $chips,
            'clear_url' => $chips === [] ? '' : self::PATH,
            // The search field submits these along with what is typed, so the rest of the filter
            // stays as it is; the field itself carries the search.
            'state'     => $this->filterMapper->toQuery($filter->withSearch('')),
        ];
    }

    public function url(AuthLogFilter $filter): string
    {
        return $this->filterMapper->url(self::PATH, $filter);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventFilter(AuthLogFilter $filter): array
    {
        $options = [['title' => __('Any'), 'url' => $this->url($filter->withEvent(null)), 'active' => $filter->event === null]];

        foreach (AuthEventType::cases() as $type) {
            $options[] = ['title' => $type->label(), 'url' => $this->url($filter->withEvent($type->value)), 'active' => $filter->event === $type->value];
        }

        return $this->control->build('event', __('Event'), $options, $this->url($filter->withEvent(null)));
    }

    /**
     * @return array<string, mixed>
     */
    private function periodFilter(AuthLogFilter $filter): array
    {
        $titles = [
            AuthLogPeriod::ANY->value   => __('Any time'),
            AuthLogPeriod::DAY->value   => __('In the last 24 hours'),
            AuthLogPeriod::WEEK->value  => __('In the last 7 days'),
            AuthLogPeriod::MONTH->value => __('In the last 30 days'),
        ];

        $options = [];
        foreach ($titles as $value => $title) {
            $period = AuthLogPeriod::from($value);
            $options[] = ['title' => $title, 'url' => $this->url($filter->withPeriod($period)), 'active' => $filter->period === $period];
        }

        return $this->control->build('period', __('Period'), $options, $this->url($filter->withPeriod(AuthLogPeriod::ANY)));
    }
}

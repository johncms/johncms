<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\Admin\Domain\Enums\UserListActivity;
use Johncms\Modules\Admin\Domain\Enums\UserListRegistered;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Enums\UserListStatus;
use Johncms\Modules\Admin\Domain\ValueObjects\UserListFilter;

/**
 * The filter of the list of users and the query string of its address, both ways.
 *
 * Every link of the page is built from a filter rather than from the address it was opened at,
 * so the links come out the same however the address was written, and a value the filter holds
 * by default is left out of them.
 *
 *     q          a part of a nickname or an e-mail, an account number, an IPv4 address
 *     status     pending | banned
 *     role       staff | the id of a role
 *     activity   online | day | week | inactive
 *     registered day | week | month | year
 *     sort, dir  the column and the direction
 */
final readonly class UserListFilterMapper
{
    private const SEARCH_LENGTH = 100;

    /**
     * @param array<string, mixed> $query The parameters of the query string. Anything that is not
     *                                    one of the values above reads as its default.
     */
    public function fromQuery(array $query): UserListFilter
    {
        $role = $this->string($query, 'role');
        $sort = UserListSort::tryFrom($this->string($query, 'sort')) ?? UserListSort::ID;

        return new UserListFilter(
            search: mb_substr(trim($this->string($query, 'q')), 0, self::SEARCH_LENGTH),
            status: UserListStatus::tryFrom($this->string($query, 'status')) ?? UserListStatus::ALL,
            staff: $role === 'staff',
            roleId: ctype_digit($role) && (int) $role > 0 ? (int) $role : null,
            activity: UserListActivity::tryFrom($this->string($query, 'activity')) ?? UserListActivity::ANY,
            registered: UserListRegistered::tryFrom($this->string($query, 'registered')) ?? UserListRegistered::ANY,
            sort: $sort,
            direction: SortDirection::tryFrom($this->string($query, 'dir')) ?? $sort->defaultDirection(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(UserListFilter $filter): array
    {
        $query = [];

        if ($filter->search !== '') {
            $query['q'] = $filter->search;
        }

        if ($filter->status !== UserListStatus::ALL) {
            $query['status'] = $filter->status->value;
        }

        if ($filter->staff) {
            $query['role'] = 'staff';
        } elseif ($filter->roleId !== null) {
            $query['role'] = (string) $filter->roleId;
        }

        if ($filter->activity !== UserListActivity::ANY) {
            $query['activity'] = $filter->activity->value;
        }

        if ($filter->registered !== UserListRegistered::ANY) {
            $query['registered'] = $filter->registered->value;
        }

        if ($filter->sort !== UserListSort::ID) {
            $query['sort'] = $filter->sort->value;
        }

        if ($filter->direction !== $filter->sort->defaultDirection()) {
            $query['dir'] = $filter->direction->value;
        }

        return $query;
    }

    public function url(string $path, UserListFilter $filter): string
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
}

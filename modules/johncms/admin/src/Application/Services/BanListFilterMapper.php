<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Modules\Admin\Domain\Enums\BanListStatus;
use Johncms\Modules\Admin\Domain\ValueObjects\BanListFilter;
use Johncms\Users\Bans\BanKind;

/**
 * The filter of the ban panel and the query string of its address, both ways.
 *
 *     q       a part of a nickname, an account number
 *     status  all — everybody who has had a ban; the bans in force by default
 *     kind    the number of a kind of ban
 *     sort    violations — the most violations first; the ban that ends last first by default
 */
final readonly class BanListFilterMapper
{
    private const SEARCH_LENGTH = 100;

    /**
     * @param array<string, mixed> $query Anything that is not one of the values above reads as
     *                                    its default.
     */
    public function fromQuery(array $query): BanListFilter
    {
        $kind = $this->string($query, 'kind');

        return new BanListFilter(
            search: mb_substr(trim($this->string($query, 'q')), 0, self::SEARCH_LENGTH),
            status: BanListStatus::tryFrom($this->string($query, 'status')) ?? BanListStatus::ACTIVE,
            kind: ctype_digit($kind) ? BanKind::tryFrom((int) $kind) : null,
            sort: BanListSort::tryFrom($this->string($query, 'sort')) ?? BanListSort::TIME,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(BanListFilter $filter): array
    {
        $query = [];

        if ($filter->search !== '') {
            $query['q'] = $filter->search;
        }

        if ($filter->status !== BanListStatus::ACTIVE) {
            $query['status'] = $filter->status->value;
        }

        if ($filter->kind !== null) {
            $query['kind'] = (string) $filter->kind->value;
        }

        if ($filter->sort !== BanListSort::TIME) {
            $query['sort'] = $filter->sort->value;
        }

        return $query;
    }

    public function url(string $path, BanListFilter $filter): string
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

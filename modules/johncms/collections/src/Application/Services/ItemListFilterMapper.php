<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Domain\Enums\ItemStatus;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;

/**
 * The filter of the list of the items of a collection and the query string of its address, both
 * ways.
 *
 *     q        a part of a name or of a code, a number
 *     section  the number of a section, 0 for the items outside every section
 *     status   published | hidden | scheduled | expired
 */
final readonly class ItemListFilterMapper
{
    private const int SEARCH_LENGTH = 100;

    /**
     * @param array<string, mixed> $query Anything that is not one of the values above reads as
     *                                    its default.
     */
    public function fromQuery(array $query): ItemListFilter
    {
        return new ItemListFilter(
            search: mb_substr(trim($this->string($query, 'q')), 0, self::SEARCH_LENGTH),
            section: $this->number($query, 'section'),
            status: ItemStatus::tryFrom($this->string($query, 'status')),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(ItemListFilter $filter): array
    {
        $query = [];

        if ($filter->search !== '') {
            $query['q'] = $filter->search;
        }

        if ($filter->section !== null) {
            $query['section'] = (string) $filter->section;
        }

        if ($filter->status !== null) {
            $query['status'] = $filter->status->value;
        }

        return $query;
    }

    public function url(string $path, ItemListFilter $filter): string
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

    /**
     * @param array<string, mixed> $query
     */
    private function number(array $query, string $key): ?int
    {
        $value = $this->string($query, $key);

        return ctype_digit($value) ? (int) $value : null;
    }
}

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
    public function fromQuery(array $query, array $settings = []): ItemListFilter
    {
        $sort = $this->string($query, 'order');
        $direction = $this->string($query, 'direction');
        $allowed = (new CollectionListSettings())->sorts();
        $enabled = $settings['filters'] ?? [];
        return new ItemListFilter(
            search: mb_substr(trim($this->string($query, 'q')), 0, self::SEARCH_LENGTH),
            section: $this->number($query, 'section'),
            status: ItemStatus::tryFrom($this->string($query, 'status')),
            explicitOrder: isset($query['order']) || isset($query['direction']),
            sortBy: isset($allowed[$sort]) ? $sort : ($settings['sort']['field'] ?? 'sort'),
            direction: in_array($direction, ['asc', 'desc'], true) ? $direction : ($settings['sort']['direction'] ?? 'asc'),
            author: in_array('author', $enabled, true) ? ($this->number($query, 'author') ?: null) : null,
            createdFrom: in_array('created_at', $enabled, true) ? $this->date($query, 'created_from') : null,
            createdTo: in_array('created_at', $enabled, true) ? $this->date($query, 'created_to') : null,
            minViews: in_array('views', $enabled, true) ? $this->number($query, 'min_views') : null,
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

        if ($filter->explicitOrder || $filter->sortBy !== 'sort' || $filter->direction !== 'asc') {
            $query['order'] = $filter->sortBy;
            $query['direction'] = $filter->direction;
        }
        foreach (['author' => $filter->author, 'created_from' => $filter->createdFrom, 'created_to' => $filter->createdTo, 'min_views' => $filter->minViews] as $key => $value) {
            if ($value !== null) {
                $query[$key] = (string) $value;
            }
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

    /** @param array<string, mixed> $query */
    private function date(array $query, string $key): ?string
    {
        $value = $this->string($query, $key);
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value) && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)) ? $value : null;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function number(array $query, string $key): ?int
    {
        $value = $this->string($query, $key);

        return ctype_digit($value) && strlen($value) <= 10 && (int) $value <= 2147483647 ? (int) $value : null;
    }
}

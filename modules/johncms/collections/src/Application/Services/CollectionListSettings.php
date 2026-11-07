<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;

final readonly class CollectionListSettings
{
    /** @return array<string, string> */
    public function columns(): array
    {
        return ['author' => d__('collections', 'Created by'), 'created_at' => d__('collections', 'Created'), 'updated_at' => d__('collections', 'Updated'), 'views' => d__('collections', 'Views'), 'section' => d__('collections', 'Section')];
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return ['author' => d__('collections', 'Author ID'), 'created_at' => d__('collections', 'Creation date'), 'views' => d__('collections', 'Minimum views')];
    }

    /** @return array<string, string> */
    public function sorts(): array
    {
        return [
            'sort' => d__('collections', 'Order'), 'id' => 'ID', 'name' => d__('collections', 'Name'),
            'created_at' => d__('collections', 'Created'), 'updated_at' => d__('collections', 'Updated'),
            'view_count' => d__('collections', 'Views'), 'active_from' => d__('collections', 'Active from'),
        ];
    }

    /** @param array<string, mixed> $settings */
    public function validate(array $settings): void
    {
        if (array_diff(array_keys($settings), ['columns', 'filters', 'sort']) !== []) {
            throw $this->invalid();
        }
        foreach (['columns' => $this->columns(), 'filters' => $this->filters()] as $key => $available) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }
            $values = $settings[$key];
            if (! is_array($values) || ! array_is_list($values)) {
                throw $this->invalid();
            }
            $seen = [];
            foreach ($values as $value) {
                if (! is_string($value) || ! isset($available[$value]) || isset($seen[$value])) {
                    throw $this->invalid();
                }
                $seen[$value] = true;
            }
        }
        if (array_key_exists('sort', $settings)) {
            $sort = $settings['sort'];
            if (! is_array($sort) || count($sort) !== 2 || ! is_string($sort['field'] ?? null) || ! isset($this->sorts()[$sort['field']]) || ! in_array($sort['direction'] ?? null, ['asc', 'desc'], true)) {
                throw $this->invalid();
            }
        }
    }

    /** @return array<string, mixed> */
    public function settings(ContentCollection $collection): array
    {
        return array_replace(
            ['columns' => ! empty($collection->settings['has_sections']) ? ['section', 'updated_at'] : ['updated_at'], 'filters' => [], 'sort' => ['field' => 'sort', 'direction' => 'asc']],
            $collection->settings['_workspace']['listing'] ?? [],
            $collection->settings['workspace']['listing'] ?? [],
        );
    }

    /** @return array<string, mixed> */
    public function controls(ContentCollection $collection, ItemListFilter $filter): array
    {
        return [
            'enabled' => $this->settings($collection)['filters'], 'sorts' => $this->sorts(),
            'sort' => $filter->sortBy, 'direction' => $filter->direction, 'author' => $filter->author,
            'created_from' => $filter->createdFrom, 'created_to' => $filter->createdTo, 'min_views' => $filter->minViews,
        ];
    }

    private function invalid(): CollectionSchemaException
    {
        return new CollectionSchemaException(d__('collections', 'Choose available list columns, filters and a valid sort direction.'));
    }
}

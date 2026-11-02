<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Modules\Collections\Domain\Query\OrderByDTO;

/**
 * Applies the filter and ordering clauses of a ContentCollectionItemQuery to an
 * Eloquent builder, supporting both base item columns and custom (EAV) fields.
 *
 * Security: neither field codes, operators nor directions are interpolated into
 * SQL. Base columns are matched against a fixed allowlist; custom fields are
 * resolved to a field id and a closed-set value column (FieldType::valueColumn());
 * operators/directions come only from enums.
 */
final class ContentCollectionItemQueryCompiler
{
    private const ITEMS_TABLE = 'collection_items';
    private const VALUES_TABLE = 'collection_item_values';

    /** @param array<string, ContentCollectionField> $fields */
    public function validate(ContentCollectionItemQuery $query, array $fields): void
    {
        (new \Johncms\Modules\Collections\Domain\Query\ItemQueryValidator())->validate($query, $fields);
    }

    /**
     * @param Builder<ContentCollectionItem> $builder
     * @param array<string, ContentCollectionField> $fields collection fields keyed by code
     */
    public function applyFilters(Builder $builder, ContentCollectionItemQuery $query, array $fields): void
    {
        if ($query->search !== null) {
            $this->applySearch($builder, $query->search);
        }
        foreach ($query->filters as $filter) {
            if (str_starts_with($filter->fieldCode, 'item.')) {
                $this->applyBaseFilter($builder, $filter);
            } else {
                $this->applyCustomFieldFilter($builder, $filter, $fields[substr($filter->fieldCode, 6)]);
            }
        }
    }

    /**
     * @param Builder<ContentCollectionItem> $builder
     * @param array<string, ContentCollectionField> $fields collection fields keyed by code
     */
    public function applyOrder(Builder $builder, ContentCollectionItemQuery $query, array $fields): void
    {
        if ($query->orderBy === []) {
            $builder->orderBy('sort');
        }
        $hasId = false;
        foreach ($query->orderBy as $order) {
            if (str_starts_with($order->fieldCode, 'item.')) {
                $builder->orderBy($this->baseColumn($order->fieldCode), $order->direction->value);
                $hasId = $hasId || $order->fieldCode === 'item.id';
            } else {
                $this->applyCustomFieldOrder($builder, $fields[substr($order->fieldCode, 6)], $order);
            }
        }
        if (! $hasId) {
            $builder->orderBy('id');
        }
    }

    /** @param Builder<ContentCollectionItem> $builder */
    public function applySearch(Builder $builder, \Johncms\Modules\Collections\Domain\Query\CollectionTextSearchDTO $search): void
    {
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search->term) . '%';
        $builder->whereExists(static function (QueryBuilder $query) use ($like, $search): void {
            $query->selectRaw('1')->from('collection_search_documents')
                ->whereColumn('collection_search_documents.item_id', 'collection_items.id')
                ->whereIn('reference', $search->references())->whereRaw("plain_text LIKE ? ESCAPE '!'", [$like]);
        });
    }

    /**
     * @param Builder<ContentCollectionItem> $builder
     */
    private function applyBaseFilter(Builder $builder, FieldFilterDTO $filter): void
    {
        if ($filter->operator === FilterOperator::In) {
            $builder->whereIn($this->baseColumn($filter->fieldCode), $this->sqlValue($filter->value));

            return;
        }

        $builder->where($this->baseColumn($filter->fieldCode), $filter->operator->sqlOperator(), $this->sqlValue($filter->value));
    }

    /**
     * @param Builder<ContentCollectionItem> $builder
     */
    private function applyCustomFieldFilter(Builder $builder, FieldFilterDTO $filter, ContentCollectionField $field): void
    {
        $valueColumn = $field->type->valueColumn();

        if ($filter->operator === FilterOperator::Eq && $filter->value === null) {
            $builder->whereNotExists(static function (QueryBuilder $query) use ($field): void {
                $query->selectRaw('1')->from(self::VALUES_TABLE)
                    ->whereColumn(self::VALUES_TABLE . '.item_id', self::ITEMS_TABLE . '.id')
                    ->where('field_id', $field->id);
            });
            return;
        }
        $value = $this->sqlValue($filter->value);
        if ($field->role === 'tags') {
            $normalize = \Johncms\Modules\Collections\Domain\Services\CollectionTextNormalizer::normalize(...);
            $value = is_array($value) ? array_map($normalize, $value) : $normalize($value);
        }
        $builder->whereExists(static function (QueryBuilder $query) use ($field, $valueColumn, $filter, $value): void {
            $query->selectRaw('1')
                ->from(self::VALUES_TABLE)
                ->whereColumn(self::VALUES_TABLE . '.item_id', self::ITEMS_TABLE . '.id')
                ->where(self::VALUES_TABLE . '.field_id', $field->id);

            if ($filter->operator === FilterOperator::In) {
                $query->whereIn($valueColumn, $value);
            } else {
                $query->where($valueColumn, $filter->operator->sqlOperator(), $value);
            }
        });
    }

    /**
     * Order by a custom field via a correlated sub-select (the field's first
     * value by sort). A subquery avoids the row multiplication a join would
     * cause for multiple-valued fields.
     *
     * @param Builder<ContentCollectionItem> $builder
     */
    private function applyCustomFieldOrder(Builder $builder, ContentCollectionField $field, OrderByDTO $order): void
    {
        $valueColumn = $field->type->valueColumn();

        $builder->orderBy(
            static function (QueryBuilder $query) use ($field, $valueColumn): void {
                $query->select($valueColumn)
                    ->from(self::VALUES_TABLE)
                    ->whereColumn(self::VALUES_TABLE . '.item_id', self::ITEMS_TABLE . '.id')
                    ->where(self::VALUES_TABLE . '.field_id', $field->id)
                    ->orderBy('sort')->orderBy('id')
                    ->limit(1);
            },
            $order->direction->value
        );
    }

    private function baseColumn(string $reference): string|\Illuminate\Database\Query\Expression
    {
        return $reference === 'item.published_at'
            ? new \Illuminate\Database\Query\Expression('COALESCE(collection_items.active_from, collection_items.created_at)')
            : substr($reference, 5);
    }

    private function sqlValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }
        return is_array($value) ? array_map($this->sqlValue(...), $value) : $value;
    }
}

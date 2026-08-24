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

    /** Base item columns allowed in filters. */
    private const FILTERABLE_COLUMNS = [
        'id', 'section_id', 'code', 'name', 'active',
        'sort', 'view_count', 'active_from', 'active_to', 'created_at', 'updated_at',
    ];

    /** Base item columns allowed for ordering. */
    private const SORTABLE_COLUMNS = [
        'id', 'sort', 'name', 'code', 'view_count', 'active_from', 'active_to', 'created_at', 'updated_at',
    ];

    /**
     * @param Builder<ContentCollectionItem> $builder
     * @param array<string, ContentCollectionField> $fields collection fields keyed by code
     */
    public function applyFilters(Builder $builder, ContentCollectionItemQuery $query, array $fields): void
    {
        foreach ($query->filters as $filter) {
            if (in_array($filter->fieldCode, self::FILTERABLE_COLUMNS, true)) {
                $this->applyBaseFilter($builder, $filter);
                continue;
            }

            $field = $fields[$filter->fieldCode] ?? null;
            if ($field !== null) {
                $this->applyCustomFieldFilter($builder, $filter, $field);
            }
            // Unknown field codes are ignored: they never reach SQL (security).
        }
    }

    /**
     * @param Builder<ContentCollectionItem> $builder
     * @param array<string, ContentCollectionField> $fields collection fields keyed by code
     */
    public function applyOrder(Builder $builder, ContentCollectionItemQuery $query, array $fields): void
    {
        $applied = false;
        foreach ($query->orderBy as $order) {
            if (in_array($order->fieldCode, self::SORTABLE_COLUMNS, true)) {
                $builder->orderBy($order->fieldCode, $order->direction->value);
                $applied = true;
                continue;
            }

            $field = $fields[$order->fieldCode] ?? null;
            if ($field !== null) {
                $this->applyCustomFieldOrder($builder, $field, $order);
                $applied = true;
            }
        }

        if (! $applied) {
            $builder->orderBy('sort')->orderBy('id');
        }
    }

    /**
     * Custom-field codes referenced by the query (i.e. not base columns). The
     * repository resolves only these to field definitions, so base-only queries
     * cost no extra lookup.
     *
     * @return list<string>
     */
    public function referencedFieldCodes(ContentCollectionItemQuery $query): array
    {
        $codes = [];
        foreach ($query->filters as $filter) {
            if (! in_array($filter->fieldCode, self::FILTERABLE_COLUMNS, true)) {
                $codes[$filter->fieldCode] = true;
            }
        }
        foreach ($query->orderBy as $order) {
            if (! in_array($order->fieldCode, self::SORTABLE_COLUMNS, true)) {
                $codes[$order->fieldCode] = true;
            }
        }

        return array_keys($codes);
    }

    /**
     * @param Builder<ContentCollectionItem> $builder
     */
    private function applyBaseFilter(Builder $builder, FieldFilterDTO $filter): void
    {
        if ($filter->operator === FilterOperator::In) {
            $builder->whereIn($filter->fieldCode, (array) $filter->value);

            return;
        }

        $builder->where($filter->fieldCode, $filter->operator->sqlOperator(), $filter->value);
    }

    /**
     * @param Builder<ContentCollectionItem> $builder
     */
    private function applyCustomFieldFilter(Builder $builder, FieldFilterDTO $filter, ContentCollectionField $field): void
    {
        $valueColumn = $field->type->valueColumn();

        $builder->whereExists(static function (QueryBuilder $query) use ($field, $valueColumn, $filter): void {
            $query->selectRaw('1')
                ->from(self::VALUES_TABLE)
                ->whereColumn(self::VALUES_TABLE . '.item_id', self::ITEMS_TABLE . '.id')
                ->where(self::VALUES_TABLE . '.field_id', $field->id);

            if ($filter->operator === FilterOperator::In) {
                $query->whereIn($valueColumn, (array) $filter->value);
            } else {
                $query->where($valueColumn, $filter->operator->sqlOperator(), $filter->value);
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
                    ->orderBy('sort')
                    ->limit(1);
            },
            $order->direction->value
        );
    }
}

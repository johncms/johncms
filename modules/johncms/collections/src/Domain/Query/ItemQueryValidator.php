<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use DateTimeImmutable;
use InvalidArgumentException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;

final class ItemQueryValidator
{
    private const BASE_TYPES = [
        'id' => FieldType::Integer, 'section_id' => FieldType::Integer,
        'code' => FieldType::String_, 'name' => FieldType::String_, 'active' => FieldType::Boolean,
        'sort' => FieldType::Integer, 'view_count' => FieldType::Integer,
        'created_by' => FieldType::Integer, 'updated_by' => FieldType::Integer,
        'active_from' => FieldType::Datetime, 'active_to' => FieldType::Datetime,
        'created_at' => FieldType::Datetime, 'updated_at' => FieldType::Datetime, 'published_at' => FieldType::Datetime,
    ];

    /** @param array<string, ContentCollectionField> $fields */
    public function validate(ContentCollectionItemQuery $query, array $fields): void
    {
        foreach ($query->fields ?? [] as $code) {
            if (! is_string($code) || ! isset($fields[$code])) {
                throw new InvalidArgumentException('Unknown selected collection field.');
            }
        }
        foreach ($query->orderBy as $order) {
            if (! $order instanceof OrderByDTO) {
                throw new InvalidArgumentException('Ordering requires OrderByDTO.');
            }
            $this->type($order->fieldCode, $fields);
        }
        foreach ($query->filters as $filter) {
            if (! $filter instanceof FieldFilterDTO) {
                throw new InvalidArgumentException('Filtering requires FieldFilterDTO.');
            }
            $type = $this->type($filter->fieldCode, $fields);
            $text = in_array($type, [FieldType::String_, FieldType::Text, FieldType::Html, FieldType::Select, FieldType::File], true);
            if (
                ($filter->operator === FilterOperator::Like && ! $text)
                || (! in_array($filter->operator, [FilterOperator::Eq, FilterOperator::In, FilterOperator::Like], true)
                    && ($text || $type === FieldType::Boolean))
            ) {
                throw new InvalidArgumentException('Unsupported operator for ' . $filter->fieldCode);
            }
            if ($filter->operator === FilterOperator::In) {
                if (! is_array($filter->value) || ! array_is_list($filter->value) || count($filter->value) > 1000) {
                    throw new InvalidArgumentException('IN requires a list of at most 1000 typed values.');
                }
                $values = $filter->value;
            } elseif ($filter->value === null && $filter->operator === FilterOperator::Eq) {
                continue;
            } else {
                $values = [$filter->value];
            }
            foreach ($values as $value) {
                $valid = match ($type) {
                    FieldType::Integer, FieldType::Relation => is_int($value),
                    FieldType::Double => (is_int($value) || is_float($value)) && is_finite((float) $value),
                    FieldType::Boolean => is_bool($value),
                    FieldType::Date, FieldType::Datetime => $value instanceof DateTimeImmutable,
                    default => is_string($value),
                };
                if (! $valid) {
                    throw new InvalidArgumentException('Invalid filter value type for ' . $filter->fieldCode);
                }
            }
        }
    }

    /** @param array<string, ContentCollectionField> $fields */
    private function type(string $reference, array $fields): FieldType
    {
        if (str_starts_with($reference, 'item.')) {
            return self::BASE_TYPES[substr($reference, 5)]
                ?? throw new InvalidArgumentException('Unknown item field: ' . $reference);
        }
        if (str_starts_with($reference, 'field.') && isset($fields[substr($reference, 6)])) {
            return $fields[substr($reference, 6)]->type;
        }
        throw new InvalidArgumentException('Use item.<column> or field.<code>: ' . $reference);
    }
}

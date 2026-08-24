<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Enums;

/**
 * Custom field data type.
 *
 * Each type maps to a single typed column in `collection_item_values`
 * (see valueColumn()) so values stay filterable/sortable in SQL.
 */
enum FieldType: string
{
    case String_ = 'string';
    case Text = 'text';
    case Html = 'html';
    case Integer = 'integer';
    case Double = 'double';
    case Boolean = 'boolean';
    case Date = 'date';
    case Datetime = 'datetime';
    case File = 'file';
    case Select = 'select';
    case Relation = 'relation';

    /**
     * The `collection_item_values` column that stores the value of this type.
     * Single source of truth for the type -> column mapping.
     */
    public function valueColumn(): string
    {
        return match ($this) {
            self::String_, self::Select, self::File => 'value_string',
            self::Text, self::Html                  => 'value_text',
            self::Integer, self::Boolean, self::Relation => 'value_int',
            self::Double                            => 'value_double',
            self::Date, self::Datetime              => 'value_date',
        };
    }

    /**
     * Cast a raw column value read from the database to its PHP type.
     */
    public function cast(mixed $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($this) {
            self::String_, self::Text, self::Html, self::Select, self::File, self::Date, self::Datetime => (string) $raw,
            self::Integer, self::Relation => (int) $raw,
            self::Boolean                 => (bool) $raw,
            self::Double                  => (float) $raw,
        };
    }
}

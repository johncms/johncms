<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Schema;

/**
 * The column types a migration may ask for.
 *
 * Closed on purpose, and closed on what the CMS actually uses. Every case has to be expressible
 * by every adapter behind SchemaInterface, so a type that exists on one database and not on the
 * next does not belong here — the escape hatch a platform-specific column would need is exactly
 * what makes a migration unable to survive a change of the layer underneath.
 */
enum ColumnType: string
{
    /** Auto-incrementing big integer, primary key. */
    case Id = 'id';

    /** Auto-incrementing unsigned integer, primary key. */
    case Increments = 'increments';

    /** Auto-incrementing unsigned big integer, primary key. */
    case BigIncrements = 'bigIncrements';

    case TinyInteger = 'tinyInteger';

    case SmallInteger = 'smallInteger';

    case Integer = 'integer';

    case BigInteger = 'bigInteger';

    case Boolean = 'boolean';

    case Double = 'double';

    case Decimal = 'decimal';

    case Char = 'char';

    case String = 'string';

    case Text = 'text';

    case MediumText = 'mediumText';

    case LongText = 'longText';

    case Json = 'json';

    case Date = 'date';

    case DateTime = 'dateTime';

    case Timestamp = 'timestamp';

    /**
     * Whether the column is filled by the database itself, which makes a default value, a null
     * and a comment meaningless on it.
     */
    public function isAutoIncrementing(): bool
    {
        return match ($this) {
            self::Id, self::Increments, self::BigIncrements => true,
            default => false,
        };
    }
}

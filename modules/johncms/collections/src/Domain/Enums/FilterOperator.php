<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Enums;

/**
 * Allowlisted comparison operators for CollectionItemQuery filters.
 *
 * Only values from this enum may reach the query builder; the operator is never
 * interpolated into SQL as a raw string.
 */
enum FilterOperator: string
{
    case Eq = '=';
    case Gt = '>';
    case Lt = '<';
    case Gte = '>=';
    case Lte = '<=';
    case Like = 'like';
    case In = 'in';

    /**
     * The SQL operator to use in a where clause. `In` is handled separately
     * (via whereIn) and has no scalar operator.
     */
    public function sqlOperator(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Enums;

/**
 * Sort direction for CollectionItemQuery ordering.
 *
 * The direction is taken only from this enum and never interpolated into SQL
 * as a raw string.
 */
enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use Johncms\Modules\Collections\Domain\Enums\SortDirection;

/**
 * A single ordering clause for CollectionItemQuery.
 *
 * `fieldCode` refers either to a base item column (resolved against an allowlist)
 * or to a custom field code; it is never interpolated into SQL directly.
 */
final readonly class OrderByDTO
{
    public function __construct(
        public string $fieldCode,
        public SortDirection $direction = SortDirection::Asc,
    ) {
    }
}

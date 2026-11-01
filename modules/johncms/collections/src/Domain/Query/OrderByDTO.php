<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use Johncms\Modules\Collections\Domain\Enums\SortDirection;

/** Ordering by item.<column> or the first value of field.<code>. */
final readonly class OrderByDTO
{
    public function __construct(
        public string $fieldCode,
        public SortDirection $direction = SortDirection::Asc,
    ) {
    }
}

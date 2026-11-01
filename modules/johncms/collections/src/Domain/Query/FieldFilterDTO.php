<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use Johncms\Modules\Collections\Domain\Enums\FilterOperator;

/** A typed comparison using item.<column> or field.<code>; IN takes a list. */
final readonly class FieldFilterDTO
{
    public function __construct(
        public string $fieldCode,
        public FilterOperator $operator,
        public mixed $value,
    ) {
    }
}

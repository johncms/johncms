<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use Johncms\Modules\Collections\Domain\Enums\FilterOperator;

/**
 * A single filter condition for CollectionItemQuery.
 *
 * `fieldCode` refers either to a base item column (resolved against an allowlist)
 * or to a custom field code (resolved to a field id in the repository). It is never
 * interpolated into SQL directly.
 */
final readonly class FieldFilterDTO
{
    public function __construct(
        public string $fieldCode,
        public FilterOperator $operator,
        public mixed $value,
    ) {
    }
}

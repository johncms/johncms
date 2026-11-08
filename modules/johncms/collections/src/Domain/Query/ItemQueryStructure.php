<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use Johncms\Modules\Collections\Domain\Services\SectionTree;

/**
 * The structure of the collection a selection reads, when the caller already has it: the fields
 * the filters and the order refer to, and the sections that decide what is visible. Without it the
 * selection reads them itself, every time.
 */
final readonly class ItemQueryStructure
{
    /** @param array<string, CollectionFieldDefinition> $fields Keyed by code. */
    public function __construct(
        public array $fields,
        public SectionTree $sections,
    ) {
    }
}

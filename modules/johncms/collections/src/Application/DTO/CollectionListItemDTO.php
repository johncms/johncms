<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * A single row of the admin collections list. The admin URLs are built in the controller; the
 * public one is empty for a collection the site does not show.
 */
final readonly class CollectionListItemDTO
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public bool $active,
        public int $sort,
        public bool $hasSections,
        public bool $public = false,
        public string $publicUrl = '',
        public int $itemCount = 0,
        public int $sectionCount = 0,
        public int $fieldCount = 0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;

/**
 * A section as the tree of the sections tab shows it: how deep it stands, and how many items
 * stand in it and under it.
 */
final readonly class SectionTreeItemDTO
{
    public function __construct(
        public ContentCollectionSection $section,
        public int $depth,
        public int $items,
        public int $itemsInSubtree,
    ) {
    }
}

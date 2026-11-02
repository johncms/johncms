<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/** A flat preorder tree node with direct and subtree item counts, including hidden items. */
final readonly class CollectionSectionTreeNodeDTO
{
    public function __construct(
        public CollectionSectionDTO $section,
        public int $depth,
        public int $items,
        public int $itemsInSubtree,
    ) {
    }
}

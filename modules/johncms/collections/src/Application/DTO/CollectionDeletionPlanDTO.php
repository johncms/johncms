<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/** Internal execution plan: parents precede children; sections are removed in reverse. */
final readonly class CollectionDeletionPlanDTO
{
    /** @param list<int> $sectionIds
     * @param list<int> $itemIds
     * @param list<int> $fieldIds
     */
    public function __construct(
        public CollectionDeletionPreviewDTO $preview,
        public array $sectionIds,
        public array $itemIds,
        public array $fieldIds,
    ) {
    }
}

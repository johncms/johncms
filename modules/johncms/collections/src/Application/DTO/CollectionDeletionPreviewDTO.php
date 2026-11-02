<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionDeletionPreviewDTO
{
    public function __construct(
        public int $collectionId,
        public ?int $sectionId,
        public string $name,
        public int $sections,
        public int $items,
        public int $fields,
        public string $revision,
    ) {
    }
}

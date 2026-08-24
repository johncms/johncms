<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * Validated input for creating or updating a collection section.
 */
final readonly class CollectionSectionFormDTO
{
    public function __construct(
        public int $collectionId,
        public ?int $parent,
        public string $code,
        public string $name,
        public ?string $description,
        public bool $active,
        public int $sort,
    ) {
    }
}

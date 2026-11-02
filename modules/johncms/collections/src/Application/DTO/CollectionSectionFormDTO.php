<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * Validated input for creating or updating a collection section.
 */
final readonly class CollectionSectionFormDTO
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public int $collectionId,
        public ?int $parent,
        public string $code,
        public string $name,
        public ?string $description,
        public bool $active,
        public int|string $sort,
        public array $metadata = [],
    ) {
    }
}

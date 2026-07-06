<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * Validated input for creating or updating a collection.
 */
final readonly class CollectionFormDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public bool $active,
        public int $sort,
        public bool $hasSections,
        public int $perPage,
    ) {
    }
}

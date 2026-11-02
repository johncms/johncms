<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * Validated input for creating or updating a collection.
 */
final readonly class CollectionFormDTO
{
    /** @param list<string>|null $searchFields Null preserves the current selection. */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public bool $active,
        public bool $public,
        public int $sort,
        public bool $hasSections,
        public int $perPage,
        public string $contentPermission = 'collections.manage',
        public ?array $searchFields = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * Validated input for creating or updating a collection item.
 *
 * `values` holds the submitted custom field values keyed by field code: a string
 * for single-valued fields, or a list of strings for multiple-valued fields.
 */
final readonly class CollectionItemFormDTO
{
    /**
     * @param array<string, string|list<string>> $values
     */
    public function __construct(
        public int $collectionId,
        public ?int $sectionId,
        public string $code,
        public string $name,
        public bool $active,
        public ?string $activeFrom,
        public ?string $activeTo,
        public int $sort,
        public ?string $previewText,
        public ?string $detailText,
        public array $values,
    ) {
    }
}
